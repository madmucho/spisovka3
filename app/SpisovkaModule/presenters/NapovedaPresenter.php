<?php

class Spisovka_NapovedaPresenter extends BasePresenter {

    public $backlink = '';

    public function actionDefault($param1, $param2, $param3)
    {

        $template = new Template();
        $template->registerFilter(new LatteFilter);
        $template->baseUri = Environment::getVariable('baseUri');
        $template->klientUri = Environment::getVariable('klientUri',Environment::getVariable('baseUri'));
        $app_info = Environment::getVariable('app_info');
        if ( !empty($app_info) ) {
            $app_info = explode("#",$app_info);
        } else {
            $app_info = array('3.x','rev.X','OSS Spisová služba v3','1270716764');
        }
        $template->AppInfo = $app_info;

        $template->setUri = "";
        $modules = array("admin","epodatelna","install","spisovka");
        if ($param1 == "param1") {
            $template->setFile = APP_DIR ."/../help/hlavni.phtml";
            $template->Napovedy = $this->napovedy();
        } else if (in_array($param1, $modules) ) {
            // Moduly
            if ( $param3 == "default" ) {
                $template->setUri = $param1 ."/". $param2;
            } else {
                $template->setUri = $param1 ."/". $param2 ."/". $param3;
            }
            $template->setFile = APP_DIR ."/../help/". ucfirst($param1) ."Module/". ucfirst($param2) ."/". $param3 .".phtml";
        } else {
            // Spisovka
            if ( $param2 == "default" ) {
                $template->setUri = $param1;
            } else {
                $template->setUri = $param1 ."/". $param2;
            }
            $template->setFile = APP_DIR ."/../help/SpisovkaModule/". ucfirst($param1) ."/". $param2 .".phtml";
        }

        include APP_DIR ."/../help/help_name.php";
        $template->HelpName = $help_name;

        $template->setFile(APP_DIR ."/../help/@layout.phtml");
        $template->render();
        $this->terminate();
    }

    private function napovedy()
    {
        $dirs = array();

        foreach (NFinder::findFiles('*.phtml')->from(APP_DIR ."/../help/") as $file) {

            $file = str_replace(APP_DIR ."/../help", "", $file);
            if ( strpos($file, "/") !== false ) {
                $file_part = explode("/",$file);
            } else {
                $file_part = explode("\\",$file);
            }
            if ( $file_part[1] == "@layout.phtml" || $file_part[1] == "hlavni.phtml" || $file_part[1] == "Default" ) {
                continue;
            }
            unset($file_part[0]);
            
            $tmp = new stdClass();
            $tmp->path = $file;
            $tmp->name = implode(" - ", $file_part);

                $param1 = strtolower(str_replace("Module", "", $file_part[1]));
                $param2 = strtolower($file_part[2]);
                $param3 = strtolower(str_replace(".phtml", "", $file_part[3]));
                if ( $param1 == "spisovka" ) {
                    $tmp->url = $param2 ."/". $param3;
                } else {
                    $tmp->url = $param1 ."/". $param2 ."/". $param3;
                }
            $tmp->code = $param1 ."/". $param2 ."/". $param3;

            eval("\$dirs['". $param1 ."']['". $param2 ."']['". $param3 ."'] = \$tmp;");

            //echo "    \$help_name['". $tmp->url ."'] = '';<br>";
            unset($tmp,$param1,$param2,$param3);
            //echo $file ." = ". implode(" - ", $file_part) ."\n";
        }

        return $dirs;

        //print_r($dirs); exit;


    }

}
