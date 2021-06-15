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
        $dir              = Node::where('id', $dirId)->first();
        $subNodeList      = Node::where('id_parent', $dir->id)->order('name', 'asc')->select();
        $assocSubNodeList = [];
        foreach ($subNodeList as $subNode) {
            $assocSubNodeList[$subNode->id] = $subNode;
        }
        $subNodeAssoc =
            AssocNodeFile::whereIn('id_node', array_keys($assocSubNodeList))->where('status', 1)->select();

        //
        $checkCover = false;
        return true;
    }

    private function clearTmpFile() {

    }
}