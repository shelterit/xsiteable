<?php

global $XS_THIS ;
include $XS_THIS.'/application/website/admin/_admin.php' ;


class xs_action_instance extends \xs\Action\Generic {

    public $metadata = array(
        'name' => "en:Admin",
        'template' => 'admin/index',
    );

    function ___action() {

        $start = '/var/www/wc/docs';
        $cmd = array();

        $model = $this->glob->request->__fetch('model', 'a');
        if (trim($model) == '')
            $model = 'a';
        

        $cmd = unserialize(file_get_contents('application/datastore/cmd.arr'));

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');


        switch ($model) {

            case 'a' :

                $admin = new _admin ( array (
                    'source' => $this->glob->dir->docs_file_source,
                    'target' => $this->glob->dir->docs_file_target,
                    'web'    => $this->glob->dir->docs_web
                ) ) ;

                $files = $admin->process_dir($this->glob->dir->docs_file_source, true);

                $res = array();

                foreach ($files as $file => $size) {
                    $f = trim(substr($file, strlen($start) + 1));
                    $s = trim(substr($f, 0, strlen($f) - 4));
                    $res[$s] = $size;
                }

                $files = explode_tree($res, '/');

                echo @json_encode($files);
                die();

                break;

            case 'b' :

                $res = array();

                $words = array();

                foreach ($cmd as $f) {
                    foreach ($f['wordimportant'] as $word => $count)
                        if (isset($words[$word][$f['small']]))
                            $words[$word][$f['small']] += $count;
                        else
                            $words[$word][$f['small']] = $count;
                }

                $final = array();
                $n = 0;

                foreach ($words as $word => $doc) {
                    $final[$word] = $doc;
                    if ($n++ > 60)
                        break;
                }

                echo @json_encode($final);
                die();

                break;
        }
    }

}
