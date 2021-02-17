<?php namespace Controller;
class Tag extends Kernel {
    function appendAct() {
    }

    function listAct() {
        $data = $this->validate(
            [
                'name'  => 'nullable|string',
                'page'  => 'nullable|integer',
                'group' => 'nullable|integer',
            ]);

        return $this->apiRet();
    }

    function addAct() {
    }

    function deleteAct() {
    }
}