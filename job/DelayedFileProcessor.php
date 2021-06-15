<?php namespace Job;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Model\AssocNodeFile;
use Model\File;
use Model\Node;

class DelayedFileProcessor {
    /**
     * 文件处理队列
     */
    public function handle($data) {
        $data += [
            'type' => '',
            'meta' => null,
        ];
        switch ($data['type']) {
            case 'delete_node':
                $this->deleteNode($data['meta']);
                break;
            case 'delete_file':
                break;
            case 'auto_cover':
                break;
            case 'clear_tmp_file':
                break;
        }
    }

    /**
     * @param $nodeId string|integer|File
     * @return bool
     */
    private function deleteNode($nodeId) {
        $node = null;
        switch (gettype($nodeId)) {
            case 'object':
                $node = $nodeId;
                break;
            default:
                $node = Node::where('id', $nodeId)->first();
                break;
        }
        if (empty($node)) return false;
        $nodeAssoc = AssocNodeFile::where('node_id', $node->id)->select();
        //
        $fileIdList = array_column($nodeAssoc, 'id_file');
        $fileList   = File::whereIn('id', $fileIdList)->select();
        foreach ($fileList as $file) {
            $this->deleteFile($file);
        }
        //
        AssocNodeFile::where('node_id', $node->id)->delete();
        Node::where('id', $nodeId)->first();
        return true;
    }

    /**
     * @param $fileId string|integer|File
     * @return bool
     */
    private function deleteFile($fileId) {
        $file = null;
        switch (gettype($fileId)) {
            case 'object':
                $file = $fileId;
                break;
            default:
                $file = File::where('id', $fileId)->first();
                break;
        }
        @unlink($file->getPath('raw', true));
        @unlink($file->getPath('normal', true));
        @unlink($file->getPath('preview', true));
        @unlink($file->getPath('alpha', true));
        File::where('id', $file->id)->delete();
        return true;
    }

    /**
     * 这边不做任何的判断，直接文件名排序的第一个封面设置成文件夹的封面
     * 所以调用前应该判断是否需要设置封面
     */
    private function autoCover($dirId) {
        $dir = null;
        switch (gettype($dirId)) {
            case 'object':
                $dir = $dirId;
                break;
            default:
                $dir = Node::where('id', $dirId)->first();
                break;
        }
        $subNodeList       = Node::where('id_parent', $dir->id)->order('name', 'asc')->select();
        $assocSubNodeList  = [];
        $subFileNodeIdList = [];
        $subNodeCoverList  = [];
        /** @var $subNode Node */
        foreach ($subNodeList as $subNode) {
            $assocSubNodeList[$subNode->id] = $subNode;
            if ($subNode->is_file == '1') {
                $subFileNodeIdList[] = $subNode->id;
            }
            if ($subNode->id_cover != '0') {
                $subNodeCoverList[] = $subNode->id_cover;
            }
        }
        //其实想想都已经在队列了干嘛非要把这一堆玩意全压成傻逼兮兮的 assoc assoc assoc 来压平循环。。。
        //主要还是写习惯了。。。
        $subNodeAssoc  =
            AssocNodeFile::whereIn('id_node', $subFileNodeIdList)->where('status', 1)->select();
        $subFileIdList = array_column($subNodeAssoc, 'id_file');
        foreach ($subNodeCoverList as $coverId) {
            $subFileIdList[] = $coverId;
        }
        $subFileIdList = array_keys(array_flip($subFileIdList));
        //
        $assocSubNodeAssoc = [];
        foreach ($subNodeAssoc as $subNodeAss) {
            $assocSubNodeAssoc[$subNodeAss->id_node] = $subNodeAss->id_file;
        }
        //获取子文件
        $subFileList      = File::whereIn('id', $subFileIdList)->where('status', 1)->select();
        $assocSubFileList = [];
        foreach ($subFileList as $subFile) {
            $assocSubFileList[$subFile->id] = $subFile;
        }
        //
        $targetCoverFile = false;
        foreach ($subNodeList as $subNode) {
            $isFile   = $subNode->is_file == '1';
            $hasCover = $subNode->id_cover == '1';
            if (!$hasCover && !$isFile) continue;
            if ($hasCover) {
                if (!empty($assocSubFileList[$subNode->id_cover])) {
                    $targetCoverFile = $assocSubFileList[$subNode->id_cover];
                    break;
                }
            }
            if ($isFile) {
                if (!empty($assocSubNodeAssoc[$subNode->id])) {
                    $subFileId = $assocSubNodeAssoc[$subNode->id];
                    if (!empty($assocSubFileList[$subFileId])) {
                        $targetCoverFile = $assocSubFileList[$subFileId];
                        break;
                    }
                }
            }
            if ($targetCoverFile) break;
        }
        if ($targetCoverFile) {
            Node::where('id', $dir->id)->update(
                ['id_cover' => $targetCoverFile->id]
            );
        }
        return true;
    }

    private function clearTmpFile() {

    }
}