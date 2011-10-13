<?php

class Spisovka_VypravnaPresenter extends BasePresenter
{

    private $typ_evidence = null;
    private $oddelovac_poradi = null;
    private $pdf_output = 0;

    public function startup()
    {
        $user_config = Environment::getVariable('user_config');
        $this->typ_evidence = 0;
        if ( isset($user_config->cislo_jednaci->typ_evidence) ) {
            $this->typ_evidence = $user_config->cislo_jednaci->typ_evidence;
        } else {
            $this->typ_evidence = 'priorace';
        }
        if ( isset($user_config->cislo_jednaci->oddelovac) ) {
            $this->oddelovac_poradi = $user_config->cislo_jednaci->oddelovac;
        } else {
            $this->oddelovac_poradi = '/';
        }
        $this->template->Oddelovac_poradi = $this->oddelovac_poradi;

        parent::startup();
    }

    public function renderDefault($filtr = null, $hledat = null, $seradit = null)
    {

        $post = $this->getRequest()->getPost();
        if ( isset($post['hromadna_submit']) ) {
            $this->actionAkce($post);
        }

        $this->template->Typ_evidence = $this->typ_evidence;

        $Dokument = new DokumentOdeslani();
        $seznam = array();
        
        // Volba vystupu - web/tisk/pdf
        if ( $this->getParam('print') ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $seznam = $Dokument->kOdeslani(1,"doporucene");
            $this->template->count_page = ceil(count($seznam)/10);
            
            $this->setLayout(false);
            $this->setView('podaciarchnew');
        } elseif ( $this->getParam('pdfprint') ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $seznam = $Dokument->kOdeslani(1,"doporucene");
            $this->pdf_output = 1;
            $this->template->count_page = ceil(count($seznam)/10);
            
            $this->setLayout(false);
            $this->setView('podaciarchnew');
        } elseif ( $this->getParam('print_balik') ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $seznam = $Dokument->kOdeslani(1,"balik");
            $this->template->count_page = ceil(count($seznam)/10);
            
            $this->setLayout(false);
            $this->setView('podaciarch');
        } elseif ( $this->getParam('pdfprint_balik') ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $seznam = $Dokument->kOdeslani(1,"balik");
            $this->pdf_output = 2;
            
            $this->template->count_page = ceil(count($seznam)/10);
            
            $this->setLayout(false);
            $this->setView('podaciarch');            
        } else {
            $seznam = $Dokument->kOdeslani();
            //$seznam = $result->fetchAll();
        }

        /*if ( count($seznam)>0 ) {
            foreach ($seznam as $subjekt_index => $subjekt) {
                $seznam[ $subjekt_index ]->druh_zasilky = @unserialize($seznam[ $subjekt_index ]->druh_zasilky);
            }
        } */     
        
        $this->template->seznam = $seznam;

    }

    protected function shutdown($response) {
        
        if ($this->pdf_output == 1 || $this->pdf_output == 2) {

            function handlePDFError($errno, $errstr, $errfile, $errline, array $errcontext)
            {
                if (0 === error_reporting()) {
                    return;
                }
                //if ( $errno == 8 ) {
                if ( strpos($errstr,'Undefined') === false ) {    
                    throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
                }
                
                
            }
            set_error_handler('handlePDFError');
            
            try {                
        
            ob_start();
            $response->send();
            $content = ob_get_clean();
            if ($content) {
                
                @ini_set("memory_limit",PDF_MEMORY_LIMIT);
                $content = str_replace("<td", "<td valign='top'", $content);
                
                if ( $this->pdf_output == 2 ) {
                    $mpdf = new mPDF('iso-8859-2', 'A4-L',9,'Helvetica');
                } else {
                    $mpdf = new mPDF('iso-8859-2', 'A4',9,'Helvetica');
                }
                
                $app_info = Environment::getVariable('app_info');
                $app_info = explode("#",$app_info);
                $app_name = (isset($app_info[2]))?$app_info[2]:'OSS Spisová služba v3';
                $mpdf->SetCreator($app_name);
                $mpdf->SetAuthor(Environment::getUser()->getIdentity()->name);
                $mpdf->SetTitle('Podací arch');                
                $mpdf->SetMargins(0, 0, 2);
                $mpdf->WriteHTML($content);
                $mpdf->Output('podaci_arch.pdf', 'I');
            }
            
            } catch (Exception $e) {
                $location = str_replace("pdfprint=1","",Environment::getHttpRequest()->getUri());
                $location = str_replace("pdfprint=2","",$location);

                echo "<h1>Nelze vygenerovat PDF výstup.</h1>";
                echo "<p>Generovaný obsah obsahuje příliš mnoho dat, které není možné zpracovat.<br />Zkuste omezit celkový počet dokumentů.</p>";
                echo "<p><a href=".$location.">Přejít na předchozí stránku.</a></p>";
                echo "<p>".$e->getMessage()."</p>";
                exit;
            }
            
        }
        
    }  
    
    public function actionAkce($data)
    {

        //echo "<pre>"; print_r($data); echo "</pre>"; exit;

        if ( isset($data['hromadna_akce']) ) {
            $DokumentOdeslani = new DokumentOdeslani();
            switch ($data['hromadna_akce']) {
                /* odeslat */
                case 'odeslat':
                    if ( isset($data['dokument_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        foreach ( $data['dokument_vyber'] as $dokument_odeslani_id ) {
                            if ( $DokumentOdeslani->odeslano($dokument_odeslani_id) ) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ( $count_ok > 0 ) {
                            $this->flashMessage('Úspěšně jste odeslal '.$count_ok.' dokumentů.');
                        }
                        if ( $count_failed > 0 ) {
                            $this->flashMessage(''.$count_failed.' dokumentů se nepodařilo odeslat!','warning');
                        }
                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect('this');
                        }
                    }
                    break;
                case 'vratit':
                    if ( isset($data['dokument_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        foreach ( $data['dokument_vyber'] as $dokument_odeslani_id ) {
                            if ( $DokumentOdeslani->vraceno($dokument_odeslani_id) ) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ( $count_ok > 0 ) {
                            $this->flashMessage('Úspěšně jste vrátil '.$count_ok.' dokumentů.');
                        }
                        if ( $count_failed > 0 ) {
                            $this->flashMessage(''.$count_failed.' dokumentů se nepodařilo vrátit!','warning');
                        }
                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect('this');
                        }
                    }
                    break;                    
                default:
                    break;
            }
            
            
        }
       
    }

    public function actionZobrazfax()
    {
        
        $DokumentOdeslani = new DokumentOdeslani();
        $id = $this->getParam('id');
        
        $dokument = $DokumentOdeslani->get($id);
        
        $this->template->dokument = $dokument;
        $this->template->isPrint = $this->getParam('print');
        
        $this->setLayout(false);
        
    }
    
    public function actionDetail()
    {
        
        $DokumentOdeslani = new DokumentOdeslani();
        $id = $this->getParam('id');
        
        $post_data = Environment::getHttpRequest()->getPost();
        if ( isset($post_data['datum_odeslani']) ) {
            // Ulozit data
            
            $row = array();
            if ( isset($post_data['datum_odeslani']) ) {
                $row['datum_odeslani'] = new DateTime( $post_data['datum_odeslani'] );
            }
                        
            $druh_zasilky_form = @$post_data['druh_zasilky'];
            if ( count($druh_zasilky_form)>0 ) {
                $druh_zasilky_a = array();
                foreach( $druh_zasilky_form as $druh_id=>$druh_status ) {
                    $druh_zasilky_a[] = $druh_id;
                }
                $row['druh_zasilky'] = serialize($druh_zasilky_a);
            } else {
                $row['druh_zasilky'] = null;
            }
                        
            if ( isset($post_data['cena_zasilky']) ) { $row['cena'] = floatval($post_data['cena_zasilky']); }
            if ( isset($post_data['hmotnost_zasilky']) ) { $row['hmotnost'] = floatval($post_data['hmotnost_zasilky']); }
            if ( isset($post_data['cislo_faxu']) ) { $row['cislo_faxu'] = $post_data['cislo_faxu']; }
            if ( isset($post_data['zprava']) ) { $row['zprava'] = $post_data['zprava']; }
            
            try {
                $DokumentOdeslani->update($row, array(array("id=%i",$id)));
                echo "###provedeno###";
                exit;
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            
        }
        
        
        $dokument = $DokumentOdeslani->get($id);
        
        if ( !empty($dokument->druh_zasilky) ) {
            $dokument->druh_zasilky = array_flip($dokument->druh_zasilky);
        }
        
        $this->template->dokument = $dokument;

        $this->template->DruhZasilky = DruhZasilky::get(null,1);
        
        $this->setLayout(false);
        
    }   
   

}

