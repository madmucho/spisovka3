<?php

class Epodatelna_DefaultPresenter extends BasePresenter
{

    protected $Epodatelna;
    protected $pdf_output = 0;

    public function __construct()
    {
        parent::__construct();
        $this->Epodatelna = new Epodatelna();
    }

    public function startup()
    {
        parent::startup();
        $this->template->original_view = $this->view;
    }

    public function renderDefault()
    {
        $this->redirect('nove');
    }

    protected function shutdown($response)
    {
        if ($this->pdf_output == 1 || $this->pdf_output == 2) {

            ob_start();
            $response->send($this->getHttpRequest(), $this->getHttpResponse());
            $content = ob_get_clean();
            if ($content) {

                @ini_set("memory_limit", PDF_MEMORY_LIMIT);

                $person_name = $this->user->displayName;

                if ($this->pdf_output == 2) {
                    $content = str_replace("<td", "<td valign='top'", $content);
                    $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                    $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                    $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s', '', $content);
                    $content = preg_replace('#<table id="table_top">.*?</table>#s', '',
                            $content);

                    $mpdf = new mPDF('iso-8859-2', 'A4', 9, 'Helvetica');

                    $app_info = new VersionInformation();
                    $app_name = $app_info->name;
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor($person_name);
                    $mpdf->SetTitle('Spisová služba - Epodatelna - Detail zprávy');

                    $mpdf->defaultheaderfontsize = 10; /* in pts */
                    $mpdf->defaultheaderfontstyle = 'B'; /* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1;  /* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9; /* in pts */
                    $mpdf->defaultfooterfontstyle = ''; /* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1;  /* 1 to include line below header/above footer */
                    $mpdf->SetHeader('||' . $this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/" . $person_name . "||{PAGENO}/{nb}"); /* defines footer for Odd and Even Pages - placed at Outer margin */

                    $mpdf->WriteHTML($content);

                    $mpdf->Output('dokument.pdf', 'I');
                } else {
                    $content = str_replace("<td", "<td valign='top'", $content);
                    $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                    $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                    $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s', '', $content);
                    $content = preg_replace('#<table id="table_top">.*?</table>#s', '',
                            $content);

                    $mpdf = new mPDF('iso-8859-2', 'A4-L', 9, 'Helvetica');

                    $app_info = new VersionInformation();
                    $app_name = $app_info->name;
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor($person_name);
                    $mpdf->SetTitle('Spisová služba - Tisk');

                    $mpdf->defaultheaderfontsize = 10; /* in pts */
                    $mpdf->defaultheaderfontstyle = 'B'; /* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1;  /* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9; /* in pts */
                    $mpdf->defaultfooterfontstyle = ''; /* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1;  /* 1 to include line below header/above footer */

                    if ($this->getParameter('typ') == 'odchozi')
                        $header = 'Seznam odchozích zpráv';
                    else if ($this->template->view == 'prichozi')
                        $header = 'Seznam příchozích zpráv';
                    else
                        $header = 'Seznam nových zpráv';
                    $mpdf->SetHeader("$header||{$this->template->Urad->nazev}");
                    $mpdf->SetFooter("{DATE j.n.Y}/" . $person_name . "||{PAGENO}/{nb}"); /* defines footer for Odd and Even Pages - placed at Outer margin */

                    $mpdf->WriteHTML($content);

                    $mpdf->Output('spisova_sluzba.pdf', 'I');
                }
            }
        }
    }

    public function renderNove()
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;


        $args = array(
            'where' => array('ep.stav = 1 AND ep.odchozi = 0')
        );
        $result = $this->Epodatelna->seznam($args);
        $paginator->itemCount = count($result);

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setView('print');
        } elseif ($pdf) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
            $this->pdf_output = 1;
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setView('print');
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $this->setView('seznam');
        }

        $this->template->seznam = $seznam;
    }

    public function renderPrichozi()
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;


        $args = null;
        $args = array(
            'where' => array('ep.stav >= 1 AND ep.odchozi = 0')
        );
        $result = $this->Epodatelna->seznam($args);
        $paginator->itemCount = count($result);

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk) {
            $seznam = $result->fetchAll();
            $this->setView('print');
        } elseif ($pdf) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
            $this->pdf_output = 1;
            $seznam = $result->fetchAll();
            $this->setView('print');
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $this->setView('seznam');
        }

        $this->template->seznam = $seznam;
    }

    public function renderOdchozi()
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;


        $args = null;
        $args = [
            'where' => ['ep.odchozi = 1'],
            'order' => ['doruceno_dne' => 'DESC']
        ];
        $result = $this->Epodatelna->seznam($args);
        $paginator->itemCount = count($result);

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
            $seznam = $result->fetchAll();
            $this->setView('printo');
        } elseif ($pdf) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
            $this->pdf_output = 1;
            $seznam = $result->fetchAll();
            $this->setView('printo');
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }

        $this->template->seznam = $seznam;
    }

    public function renderDetail($id)
    {
        $zprava = new EpodatelnaMessage($id);

        $this->template->Zprava = $zprava;
        $this->template->Prilohy = EpodatelnaPrilohy::getFileList($zprava, $this->storage);

        if ($zprava->typ == 'I') {
            if (!empty($zprava->file_id)) {
                $source = self::nactiISDS($this->storage, $zprava->file_id);
                $signature_info = $source ? unserialize($source) : null;
                if (empty($signature_info->dmAcceptanceTime)) {
                    $this->zkontrolujOdchoziISDS($zprava); // Co toto dela?
                }
            }
        }
        if ($zprava->typ == 'E') {
            $this->addComponent(new Spisovka\Components\EmailSignature($zprava, $this->storage),
                    'emailSignature');
        }

        if (!empty($zprava->dokument_id)) {
            $Dokument = new Dokument();
            $this->template->Dokument = $Dokument->getInfo($zprava->dokument_id);
        } else {
            $this->template->Dokument = null;
        }

        if ($this->getParameter('pdfprint'))
            $this->pdf_output = 2;
    }

    public function renderOdetail($id)
    {
        $this->renderDetail($id);
    }

    public function renderZkontrolovat()
    {
        new SeznamStatu($this, 'seznamstatu');
    }

    // Stáhne zprávy ze všech schránek a dá uživateli vědět výsledek

    public function actionZkontrolovatAjax()
    {
        @set_time_limit(120); // z moznych dusledku vetsich poctu polozek je nastaven timeout

        /* $id = $this->getParameter('id',null);
          $typ = substr($id,0,1);
          $index = substr($id,1); */

        $config_data = (new Spisovka\ConfigEpodatelna())->get();
        $result = array();

        $nalezena_aktivni_schranka = 0;

        // kontrola ISDS
        $zkontroluj_isds = 1;
        if (count($config_data['isds']) > 0 && $zkontroluj_isds == 1) {
            foreach ($config_data['isds'] as $index => $isds_config) {
                if ($isds_config['aktivni'] != 1)
                    continue;
                if ($isds_config['podatelna'] && !OrgJednotka::isInOrg($isds_config['podatelna']))
                    continue;

                $nalezena_aktivni_schranka = 1;
                $zprava = $this->downloadISDS($isds_config);
                echo "$zprava<br />";
            }
        }
        // kontrola emailu
        $zkontroluj_email = 1;
        if (count($config_data['email']) > 0 && $zkontroluj_email == 1) {
            foreach ($config_data['email'] as $index => $email_config) {
                if ($email_config['aktivni'] != 1)
                    continue;
                if ($email_config['podatelna'] && !OrgJednotka::isInOrg($email_config['podatelna']))
                    continue;

                $nalezena_aktivni_schranka = 1;
                $result = $this->downloadEmails($email_config);
                if (is_string($result))
                    echo $result . '<br />';
                else if ($result > 0) {
                    echo "Z emailové schránky \"" . $email_config['ucet'] . "\" bylo přijato $result nových zpráv.<br />";
                } else {
                    echo 'Z emailové schránky "' . $email_config['ucet'] . '" nebyly zjištěny žádné nové zprávy.<br />';
                }
            }
        }

        if (!$nalezena_aktivni_schranka)
            echo 'Žádná schránka není definována nebo nastavena jako aktivní.<br />';

        $this->terminate();
    }

    public function actionZkontrolovatOdchoziISDS()
    {
        // @set_time_limit(600);
        $this->zkontrolujOdchoziISDS();
        exit;
    }

    public function actionNactiNoveAjax()
    {
        $SubjektModel = new Subjekt();
        $isds_subjekt_cache = [];
        $email_subjekt_cache = [];

        //$client_config = Environment::getVariable('client_config');
        //$vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        //$paginator = $vp->getPaginator();
        //$paginator->itemsPerPage = 2;// isset($client_config->nastaveni->pocet_polozek)?$client_config->nastaveni->pocet_polozek:20;

        $args = array(
            'where' => array('(ep.stav = 0 OR ep.stav = 1) AND ep.odchozi = 0')
        );
        $result = $this->Epodatelna->seznam($args);
        //$paginator->itemCount = count($result);
        $zpravy = $result->fetchAll(); //$paginator->offset, $paginator->itemsPerPage);

        if (!$zpravy)
            $zpravy = null;
        else
            foreach ($zpravy as $zprava) {

                unset($zprava->identifikator);

                $prilohy = unserialize($zprava->prilohy);
                if ($zprava->typ == 'I' && $prilohy !== false)
                    $zprava->prilohy = $prilohy;
                else
                    $zprava->prilohy = false;

                $subjekt = new stdClass();
                $subjekt->mesto = '';
                $subjekt->psc = '';
                $subjekt->ulice = '';
                $subjekt->cp = '';
                $subjekt->co = '';
                $subjekt->jmeno = '';
                $subjekt->prijmeni = '';

                $original = null;
                $nalezene_subjekty = null;
                if ($zprava->typ == 'E') {
                    // Nacteni originalu emailu
                    if (!empty($zprava->file_id)) {
                        $sender = $zprava->odesilatel;
                        $matches = [];
                        if (preg_match('/(.*)<(.*)>/', $sender, $matches)) {
                            $subjekt->email = $matches[2];
                            $subjekt->nazev_subjektu = trim($matches[1]);
                            $subjekt->prijmeni = $subjekt->nazev_subjektu;
                        } else {
                            $subjekt->email = $sender;
                            $subjekt->nazev_subjektu = null;
                        }
                        $matches = [];
                        if (preg_match('/^(.*) ([^ ]*)$/', $subjekt->prijmeni, $matches)) {
                            $subjekt->jmeno = $matches[1];
                            $subjekt->prijmeni = $matches[2];
                        }

                        if (!isset($email_subjekt_cache[$subjekt->email])) {
                            $search = ['email' => $subjekt->email, 'nazev_subjektu' => $subjekt->nazev_subjektu];
                            $search = \Nette\Utils\ArrayHash::from($search);
                            $email_subjekt_cache[$subjekt->email] = $SubjektModel->hledat($search,
                                    'email', true);
                        }
                        $nalezene_subjekty = $email_subjekt_cache[$subjekt->email];
                    }
                } else if ($zprava->typ == 'I') {
                    // Nacteni originalu DS
                    if (!empty($zprava->file_id)) {
                        $file_id = explode("-", $zprava->file_id);
                        $original = self::nactiISDS($this->storage, $file_id[0]);
                        $original = unserialize($original);

                        // odebrat obsah priloh, aby to neotravovalo
                        unset($original->dmDm->dmFiles);

                        $subjekt->id_isds = $original->dmDm->dbIDSender;
                        $subjekt->nazev_subjektu = $original->dmDm->dmSender;
                        $subjekt->type = ISDS_Spisovka::typDS($original->dmDm->dmSenderType);
                        if (isset($original->dmDm->dmSenderAddress)) {
                            $res = ISDS_Spisovka::parseAddress($original->dmDm->dmSenderAddress);
                            foreach ($res as $key => $value)
                                $subjekt->$key = $value;
                        }

                        if (!isset($isds_subjekt_cache[$subjekt->id_isds]))
                            $isds_subjekt_cache[$subjekt->id_isds] = $SubjektModel->hledat($subjekt,
                                    'isds', true);
                        $nalezene_subjekty = $isds_subjekt_cache[$subjekt->id_isds];
                    }
                }

                $zprava->subjekt = ['original' => $subjekt, 'databaze' => $nalezene_subjekty];

                $doruceno_dne = strtotime($zprava->doruceno_dne);
                $zprava->doruceno_dne_datum = date("j.n.Y", $doruceno_dne);
                $zprava->doruceno_dne_cas = date("G:i:s", $doruceno_dne);
                $zprava->odesilatel = htmlspecialchars($zprava->odesilatel);
            }

        $this->sendJson($zpravy);
    }

    /**
     * @param array    $ISDS_box
     * @return string  Zprava pro uzivatele
     */
    protected function downloadISDS($ISDS_box)
    {
        $isds = new ISDS_Spisovka();

        try {
            $isds->pripojit($ISDS_box);

            $od = $this->Epodatelna->getLastISDS();
            $do = time() + 7200;

            $UploadFile = $this->storage;

            $pocet_novych_zprav = 0;
            $zpravy = $isds->seznamPrichozichZprav($od, $do);

            if ($zpravy)
                foreach ($zpravy as $z)
                // kontrola existence v epodatelny
                    if (!$this->Epodatelna->existuje($z->dmID, 'isds')) {
                        // nova zprava, ktera neni nahrana v epodatelne
                        // [P.L.] - cache neni vubec funkcni, neukladaly se tam vysledky
                        // $storage = new FileStorage(TEMP_DIR);
                        // $cache = new Cache($storage); // nebo $cache = Environment::getCache()
                        // if (isset($cache['zkontrolovat_isds_'.$z->dmID])):
                        // $mess = $cache['zkontrolovat_isds_'.$z->dmID];
                        // else:

                        $mess = $isds->prectiZpravu($z->dmID);
                        // endif;
                        //echo "<pre>";
                        /*
                          dmDm = objekt
                          dmDm->dmFiles
                          dmHash = objekt
                          dmQTimestamp = string
                          dmDeliveryTime = 2010-05-11T12:24:13.242+02:00
                          dmAcceptanceTime = 2010-05-11T12:26:53.899+02:00
                          dmMessageStatus = 6
                          dmAttachmentSize = 260

                         */
                        /* foreach( $mess->dmDm->dmFiles->dmFile[0] as $k => $m ) {

                          if ( $k == 'dmEncodedContent' ) continue;
                          if ( is_object($m) ) {
                          echo $k ." = objekt\n";
                          } else {
                          echo $k ." = ". $m ."\n";
                          }


                          } */



                        /*
                          dmID = 342682
                          dbIDSender = hjyaavk
                          dmSender = Město Milotice
                          dmSenderAddress = Kovářská 14/1, 37612 Milotice, CZ
                          dmSenderType = 10
                          dmRecipient = Společnost pro výzkum a podporu OpenSource
                          dmRecipientAddress = 40501 Děčín, CZ
                          dmAmbiguousRecipient =
                          dmSenderOrgUnit =
                          dmSenderOrgUnitNum =
                          dbIDRecipient = pksakua
                          dmRecipientOrgUnit =
                          dmRecipientOrgUnitNum =
                          dmToHands =
                          dmAnnotation = Vaše datová zpráva byla přijata
                          dmRecipientRefNumber = KAV-34/06-ŘKAV/2010
                          dmSenderRefNumber = AB-44656
                          dmRecipientIdent = 0.06.00
                          dmSenderIdent = ZN-161
                          dmLegalTitleLaw =
                          dmLegalTitleYear =
                          dmLegalTitleSect =
                          dmLegalTitlePar =
                          dmLegalTitlePoint =
                          dmPersonalDelivery =
                          dmAllowSubstDelivery =
                          dmFiles = objekt
                         */

                        $annotation = empty($mess->dmDm->dmAnnotation) ? "(Datová zpráva č. " . $mess->dmDm->dmID . ")"
                                    : $mess->dmDm->dmAnnotation;

                        $popis = '';
                        $popis .= "ID datové zprávy    : " . $mess->dmDm->dmID . "\n"; // = 342682
                        $popis .= "Věc, předmět zprávy : " . $annotation . "\n"; //  = Vaše datová zpráva byla přijata
                        $popis .= "\n";
                        $popis .= "Číslo jednací odesílatele   : " . $mess->dmDm->dmSenderRefNumber . "\n"; //  = AB-44656
                        $popis .= "Spisová značka odesílatele : " . $mess->dmDm->dmSenderIdent . "\n"; //  = ZN-161
                        $popis .= "Číslo jednací příjemce     : " . $mess->dmDm->dmRecipientRefNumber . "\n"; //  = KAV-34/06-ŘKAV/2010
                        $popis .= "Spisová značka příjemce    : " . $mess->dmDm->dmRecipientIdent . "\n"; //  = 0.06.00
                        $popis .= "\n";
                        $popis .= "Do vlastních rukou? : " . (!empty($mess->dmDm->dmPersonalDelivery)
                                            ? "ano" : "ne") . "\n"; //  =
                        $popis .= "Doručeno fikcí?     : " . (!empty($mess->dmDm->dmAllowSubstDelivery)
                                            ? "ano" : "ne") . "\n"; //  =
                        $popis .= "Zpráva určena pro   : " . $mess->dmDm->dmToHands . "\n"; //  =
                        $popis .= "\n";
                        $popis .= "Odesílatel:\n";
                        $popis .= "            " . $mess->dmDm->dbIDSender . "\n"; //  = hjyaavk
                        $popis .= "            " . $mess->dmDm->dmSender . "\n"; //  = Město Milotice
                        $popis .= "            " . $mess->dmDm->dmSenderAddress . "\n"; //  = Kovářská 14/1, 37612 Milotice, CZ
                        $popis .= "            " . $mess->dmDm->dmSenderType . " - " . ISDS_Spisovka::typDS($mess->dmDm->dmSenderType) . "\n"; //  = 10
                        $popis .= "            org.jednotka: " . $mess->dmDm->dmSenderOrgUnit . " [" . $mess->dmDm->dmSenderOrgUnitNum . "]\n"; //  =
                        $popis .= "\n";
                        $popis .= "Příjemce:\n";
                        $popis .= "            " . $mess->dmDm->dbIDRecipient . "\n"; //  = pksakua
                        $popis .= "            " . $mess->dmDm->dmRecipient . "\n"; //  = Společnost pro výzkum a podporu OpenSource
                        $popis .= "            " . $mess->dmDm->dmRecipientAddress . "\n"; //  = 40501 Děčín, CZ
                        //$popis .= "Je příjemce ne-OVM povýšený na OVM: ". $mess->dmDm->dmAmbiguousRecipient ."\n";//  =
                        $popis .= "            org.jednotka: " . $mess->dmDm->dmRecipientOrgUnit . " [" . $mess->dmDm->dmRecipientOrgUnitNum . "]\n"; //  =
                        $popis .= "\n";
                        $popis .= "Status: " . $mess->dmMessageStatus . " - " . ISDS_Spisovka::stavZpravy($mess->dmMessageStatus) . "\n";
                        $dt_dodani = strtotime($mess->dmDeliveryTime);
                        $dt_doruceni = strtotime($mess->dmAcceptanceTime);
                        $popis .= "Datum a čas dodání   : " . date("j.n.Y G:i:s", $dt_dodani) . " (" . $mess->dmDeliveryTime . ")\n"; //  =
                        $popis .= "Datum a čas doručení : " . date("j.n.Y G:i:s", $dt_doruceni) . " (" . $mess->dmAcceptanceTime . ")\n"; //  =
                        $popis .= "Přibližná velikost všech příloh : " . $mess->dmAttachmentSize . "kB\n"; //  =
                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleLaw ."\n";//  =
                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleYear ."\n";//  =
                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleSect ."\n";//  =
                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitlePar ."\n";//  =
                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitlePoint ."\n";//  =

                        $zprava = array();
                        $zprava['odchozi'] = 0;
                        $zprava['typ'] = 'I';
                        $zprava['poradi'] = $this->Epodatelna->getMax();
                        $zprava['rok'] = date('Y');
                        $zprava['isds_id'] = $z->dmID;
                        $zprava['predmet'] = $annotation;
                        $zprava['popis'] = $popis;
                        $zprava['odesilatel'] = $z->dmSender . ', ' . $z->dmSenderAddress;
                        //$zprava['odesilatel_id'] = $z->dmAnnotation;
                        $zprava['adresat'] = $ISDS_box['ucet'] . ' [' . $ISDS_box['idbox'] . ']';
                        $zprava['prijato_dne'] = new DateTime();
                        $zprava['doruceno_dne'] = new DateTime($z->dmAcceptanceTime);
                        $zprava['user_id'] = $this->user->id;

                        /*
                          dmEncodedContent = obsah
                          dmMimeType = application/pdf
                          dmFileMetaType = main
                          dmFileGuid =
                          dmUpFileGuid =
                          dmFileDescr = odpoved_OVM.pdf
                          dmFormat =
                         */
                        $prilohy = array();
                        if (isset($mess->dmDm->dmFiles->dmFile)) {
                            foreach ($mess->dmDm->dmFiles->dmFile as $index => $file) {
                                $prilohy[] = array(
                                    'name' => $file->dmFileDescr,
                                    'size' => strlen($file->dmEncodedContent),
                                    'mimetype' => $file->dmMimeType,
                                    'id' => $index
                                );
                            }
                        }
                        $zprava['prilohy'] = serialize($prilohy);

                        //$zprava['evidence'] = $z->dmAnnotation;
                        //$zprava['dokument_id'] = $z->dmAnnotation;
                        $zprava['stav'] = 0;
                        $zprava['stav_info'] = '';

                        //print_r($zprava);
                        //exit;

                        if ($epod_id = $this->Epodatelna->insert($zprava)) {

                            /* Ulozeni podepsane ISDS zpravy */
                            $data = array(
                                'filename' => 'ep_isds_' . $epod_id . '.zfo',
                                'dir' => 'EP-I-' . sprintf('%06d', $zprava['poradi']) . '-' . $zprava['rok'],
                                'typ' => '5',
                                'popis' => 'Podepsaný originál ISDS zprávy z epodatelny ' . $zprava['poradi'] . '-' . $zprava['rok']
                                    //'popis'=>'Emailová zpráva'
                            );

                            $signedmess = $isds->SignedMessageDownload($z->dmID);

                            if ($file_o = $UploadFile->uploadEpodatelna($signedmess, $data)) {
                                // ok
                            } else {
                                $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                                // false
                            }

                            /* Ulozeni reprezentace zpravy */
                            $data = array(
                                'filename' => 'ep_isds_' . $epod_id . '.bsr',
                                'dir' => 'EP-I-' . sprintf('%06d', $zprava['poradi']) . '-' . $zprava['rok'],
                                'typ' => '5',
                                'popis' => 'Byte-stream reprezentace ISDS zprávy z epodatelny ' . $zprava['poradi'] . '-' . $zprava['rok']
                                    //'popis'=>'Emailová zpráva'
                            );

                            if ($file = $UploadFile->uploadEpodatelna(serialize($mess), $data)) {
                                // ok
                                $zprava['stav_info'] = 'Zpráva byla uložena';
                                $zprava['file_id'] = $file->id;
                                $this->Epodatelna->update(
                                        array('stav' => 1,
                                    'stav_info' => $zprava['stav_info'],
                                    'file_id' => $file->id,
                                        ), array(array('id=%i', $epod_id))
                                );
                            } else {
                                // toto se nikam neulozi!
                                $zprava['stav_info'] = 'Reprezentace zprávy se nepodařilo uložit';
                                // false
                            }
                        } else {
                            // a toto rovnez ne
                            $zprava['stav_info'] = 'Zprávu se nepodařilo uložit';
                        }

                        $pocet_novych_zprav++;
                        unset($zprava);
                    }

            if ($pocet_novych_zprav)
                return "Z ISDS schránky \"{$ISDS_box['ucet']}\" bylo přijato $pocet_novych_zprav nových zpráv.";

            return "Z ISDS schránky \"{$ISDS_box['ucet']}\" nebyly zjištěny žádné nové zprávy.";
        } catch (Exception $e) {
            return "Při kontrole schránky \"{$ISDS_box['ucet']}\" došlo k chybě: " . $e->getMessage();
        }
    }

    public function zkontrolujOdchoziISDS($zprava = null)
    {
        $ep_zpravy = array();
        $now = getdate();
        $od = mktime(0, 0, 0, $now['mon'], $now['mday'] - 1, $now['year']);
        $do = mktime(0, 0, 0, $now['mon'], $now['mday'] + 1, $now['year']);

        if (is_null($zprava)) {
            // Nacti zpravy, ktere nemaji datum doruceni
            $args = array(
                'where' => array('ep.odchozi = 1', 'ep.typ = \'I\'', 'ep.prijato_dne = ep.doruceno_dne')
            );
            $epod = $this->Epodatelna->seznam($args)->fetchAll();
            if (count($epod) > 0) {
                foreach ($epod as $zprava) {
                    $datum = strtotime($zprava->prijato_dne);
                    if ($od > ($datum - 36000))
                        $od = $datum - 36000;
                    if ($do < ($datum + 36000))
                        $do = $datum + 36000;

                    $ep_zpravy[$zprava->isds_id] = array(
                        'id_mess' => $zprava->isds_id,
                        'epodatelna_id' => $zprava->id,
                        'datum_odeslani' => $zprava->prijato_dne,
                        'datum_doruceni' => $zprava->doruceno_dne,
                        'poradi' => $zprava->poradi,
                        'rok' => $zprava->rok
                    );
                }
            }
        } else {
            $datum = strtotime($zprava->prijato_dne);
            $od = $datum - 36000;
            $do = $datum + 36000;

            $ep_zpravy[$zprava->isds_id] = array(
                'id_mess' => $zprava->isds_id,
                'epodatelna_id' => $zprava->id,
                'datum_odeslani' => $zprava->prijato_dne,
                'datum_doruceni' => $zprava->doruceno_dne,
                'poradi' => $zprava->poradi,
                'rok' => $zprava->rok
            );
        }

        if (count($ep_zpravy) == 0)
            return false; // neni co kontrolovat

        $config_data = (new Spisovka\ConfigEpodatelna())->get();
        $config = $config_data['isds'][0];

        $isds = new ISDS_Spisovka();

        try {
            $isds->pripojit($config);
        } catch (Exception $e) {
            $this->flashMessage('Nepodařilo se připojit k ISDS schránce "' . $config['ucet'] . '"!
                                  ISDS chyba: ' . $e->getMessage(), 'warning');
            return null;
        }

        $zpravy = $isds->seznamOdeslanychZprav($od, $do);

        if (count($zpravy) > 0) {
            $tmp = array();

            $UploadFile = $this->storage;

            foreach ($zpravy as $mess) {

                if (!isset($ep_zpravy[$mess->dmID]))
                    continue;

                $annotation = empty($mess->dmAnnotation) ? "(Datová zpráva č. " . $mess->dmID . ")"
                            : $mess->dmAnnotation;

                $popis = '';
                $popis .= "ID datové zprávy    : " . $mess->dmID . "\n"; // = 342682
                $popis .= "Věc, předmět zprávy : " . $annotation . "\n"; //  = Vaše datová zpráva byla přijata
                $popis .= "\n";
                $popis .= "Číslo jednací odesílatele   : " . $mess->dmSenderRefNumber . "\n"; //  = AB-44656
                $popis .= "Spisová značka odesílatele : " . $mess->dmSenderIdent . "\n"; //  = ZN-161
                $popis .= "Číslo jednací příjemce     : " . $mess->dmRecipientRefNumber . "\n"; //  = KAV-34/06-ŘKAV/2010
                $popis .= "Spisová značka příjemce    : " . $mess->dmRecipientIdent . "\n"; //  = 0.06.00
                $popis .= "\n";
                $popis .= "Do vlastních rukou? : " . (!empty($mess->dmPersonalDelivery) ? "ano"
                                    : "ne") . "\n"; //  =
                $popis .= "Doručeno fikcí?     : " . (!empty($mess->dmAllowSubstDelivery) ? "ano"
                                    : "ne") . "\n"; //  =
                $popis .= "Zpráva určena pro   : " . $mess->dmToHands . "\n"; //  =
                $popis .= "\n";
                $popis .= "Odesílatel:\n";
                $popis .= "            " . $mess->dbIDSender . "\n"; //  = hjyaavk
                $popis .= "            " . $mess->dmSender . "\n"; //  = Město Milotice
                $popis .= "            " . $mess->dmSenderAddress . "\n"; //  = Kovářská 14/1, 37612 Milotice, CZ
                $popis .= "            " . $mess->dmSenderType . " - " . ISDS_Spisovka::typDS($mess->dmSenderType) . "\n"; //  = 10
                $popis .= "            org.jednotka: " . $mess->dmSenderOrgUnit . " [" . $mess->dmSenderOrgUnitNum . "]\n"; //  =
                $popis .= "\n";
                $popis .= "Příjemce:\n";
                $popis .= "            " . $mess->dbIDRecipient . "\n"; //  = pksakua
                $popis .= "            " . $mess->dmRecipient . "\n"; //  = Společnost pro výzkum a podporu OpenSource
                $popis .= "            " . $mess->dmRecipientAddress . "\n"; //  = 40501 Děčín, CZ
                $popis .= "            org.jednotka: " . $mess->dmRecipientOrgUnit . " [" . $mess->dmRecipientOrgUnitNum . "]\n"; //  =
                $popis .= "\n";
                $popis .= "Status: " . $mess->dmMessageStatus . " - " . ISDS_Spisovka::stavZpravy($mess->dmMessageStatus) . "\n";
                $dt_dodani = strtotime($mess->dmDeliveryTime);
                $dt_doruceni = strtotime($mess->dmAcceptanceTime);
                $popis .= "Datum a čas dodání   : " . date("j.n.Y G:i:s", $dt_dodani) . " (" . $mess->dmDeliveryTime . ")\n"; //  =
                if ($dt_doruceni == 0) {
                    $popis .= "Datum a čas doručení : (příjemce zprávu zatím nepřijal)\n"; //  =
                } else {
                    $popis .= "Datum a čas doručení : " . date("j.n.Y G:i:s", $dt_doruceni) . " (" . $mess->dmAcceptanceTime . ")\n"; //  =
                }
                $popis .= "Přibližná velikost všech příloh : " . $mess->dmAttachmentSize . "kB\n"; //  =

                $zprava = array();
                $zprava['popis'] = $popis;
                if (!empty($mess->dmAcceptanceTime)) {
                    $zprava['doruceno_dne'] = new DateTime($mess->dmAcceptanceTime);
                }

                $epod_id = $ep_zpravy[$mess->dmID]['epodatelna_id'];
                $this->Epodatelna->update($zprava, array(array('id=%i', $epod_id)));

                /* Ulozeni podepsane ISDS zpravy */
                $data = array(
                    'filename' => 'ep_isds_' . $epod_id . '.zfo',
                    'dir' => 'EP-O-' . sprintf('%06d', $ep_zpravy[$mess->dmID]['poradi']) . '-' . $ep_zpravy[$mess->dmID]['rok'],
                    'typ' => '5',
                    'popis' => 'Podepsaný originál ISDS zprávy z epodatelny ' . $ep_zpravy[$mess->dmID]['poradi'] . '-' . $ep_zpravy[$mess->dmID]['rok']
                );

                $signedmess = $isds->SignedSentMessageDownload($mess->dmID);

                if ($file_o = $UploadFile->uploadEpodatelna($signedmess, $data)) {
                    // ok
                } else {
                    $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                    // false
                }

                /* Ulozeni reprezentace zpravy */
                $data = array(
                    'filename' => 'ep_isds_' . $epod_id . '.bsr',
                    'dir' => 'EP-O-' . sprintf('%06d', $ep_zpravy[$mess->dmID]['poradi']) . '-' . $ep_zpravy[$mess->dmID]['rok'],
                    'typ' => '5',
                    'popis' => 'Byte-stream reprezentace ISDS zprávy z epodatelny ' . $ep_zpravy[$mess->dmID]['poradi'] . '-' . $ep_zpravy[$mess->dmID]['rok']
                );

                if ($file = $UploadFile->uploadEpodatelna(serialize($mess), $data)) {
                    // ok
                    $zprava['stav_info'] = 'Zpráva byla uložena';
                    $zprava['file_id'] = $file->id;
                    $this->Epodatelna->update(
                            array('stav' => 1,
                        'stav_info' => $zprava['stav_info'],
                        'file_id' => $file->id,
                            ), array(array('id=%i', $epod_id))
                    );
                } else {
                    $zprava['stav_info'] = 'Reprezentace zprávy se nepodařilo uložit';
                    // false
                }

                $tmp[] = $zprava;
                unset($zprava);
                //break;
            }
        }

        return ( count($tmp) > 0 ) ? $tmp : null;
    }

    /** Stáhne nové zprávy z emailové schránky a uloží je do e-podatelny.
     * @param array $mailbox
     * @return string|int  počet nových zpráv nebo řetězec s popisem chyby
     */
    protected function downloadEmails($mailbox)
    {
        $imap = new ImapClient();
        $connection_string = '{' . $mailbox['server'] . ':' . $mailbox['port'] . '' . $mailbox['typ'] . '}' . $mailbox['inbox'];

        $success = $imap->connect($connection_string, $mailbox['login'], $mailbox['password']);
        if (!$success) {
            $msg = 'Nepodařilo se připojit k emailové schránce "' . $mailbox['ucet'] . '"!<br />
                    IMAP chyba: ' . $imap->error();
            return $msg;
        }

        if (!$imap->count_messages()) {
            //  nejsou žádné zprávy k přijetí
            $imap->close();
            return 0;
        }


        $UploadFile = $this->storage;

        $messages = $imap->get_all_messages();
        $messages_recorded = 0;

        foreach ($messages as $message) {
            // kontrola existence v epodatelne
            // chybi-li Message ID, jedna se pravdepodobne o Spam
            if (!isset($message->message_id) || $this->Epodatelna->existuje($message->message_id,
                            'email'))
                continue;

            // nova zprava, ktera neni nahrana v epodatelne
            // Nejprve uvolni pamet predchozi zpravy
            $raw_message = null;

            // Nacteni kompletni zpravy
            $structure = $imap->get_message_structure($message->Msgno);
            $raw_message = $imap->get_raw_message($message->Msgno);
            // Preved do formatu mailbox, jinak nebude IMAP knihovna fungovat
            $raw_message = "From unknown  Sat Jan  1 00:00:00 2000\r\n" . $raw_message;

            $popis = $imap->find_plain_text($message->Msgno, $structure);
            if (!$popis)
                $popis = '';
            if (strlen($popis) > 10000)
                $popis = substr($popis, 0, 10000);

            if (empty($message->subject)) {
                $predmet = "[Bez předmětu] Emailová zpráva";
                if (!empty($message->fromaddress))
                    $predmet .= " od $message->fromaddress";
            } else
                $predmet = $message->subject;

            $insert = array();
            $insert['odchozi'] = 0;
            $insert['typ'] = 'E';
            $insert['poradi'] = $this->Epodatelna->getMax();
            $insert['rok'] = date('Y');
            $insert['email_id'] = $message->message_id;
            $insert['predmet'] = $predmet;
            $insert['popis'] = $popis;
            $insert['odesilatel'] = $message->fromaddress;
            $insert['adresat'] = $mailbox['ucet']; // označení uživatele pro e-mailovou schránku
            $insert['prijato_dne'] = new DateTime();
            $insert['doruceno_dne'] = new DateTime(date('Y-m-d H:i:s', $message->udate));
            $insert['user_id'] = $this->user->id;

            // Prilohy zjistujeme pokazde, kdyz je to potreba, aby bylo mozno zmenit/opravit
            // chovani aplikace
            $insert['prilohy'] = null;

            $insert['stav'] = 0;
            $insert['stav_info'] = '';
            $insert['file_id'] = null;

            // Test na pritomnost digitalniho podpisu
            $insert['email_signed'] = $imap->is_signed($structure);
            if ($mailbox['only_signature'] == true) {
                if (!$insert['email_signed']) {
                    // email neobsahuje epodpis
                    $insert['stav'] = 100;
                    $insert['stav_info'] = 'Emailová zpráva byla odmítnuta. Neobsahuje elektronický podpis.';
                } else if ($mailbox['qual_signature'] == true) {
                    // pouze kvalifikovane
                    $tmp_filename = tempnam(TEMP_DIR, 'emailtest');
                    file_put_contents($tmp_filename, $raw_message);
                    $esign = new esignature();
                    $result = $esign->verifySignature($tmp_filename);
                    unlink($tmp_filename);
                    if (!$result['ok']) {
                        // neobsahuje kvalifikovany epodpis
                        $insert['stav'] = 100;
                        $insert['stav_info'] = 'Emailová zpráva byla odmítnuta. Neobsahuje kvalifikovaný elektronický podpis';
                    }
                }
            }

            $epod_id = $this->Epodatelna->insert($insert);

            if ($insert['stav'] == 100)
                continue; // odmitnout, nepokracovat dale. TODO: odeslat upozorneni odesilateli.

            $data = array(
                'filename' => 'ep_email_' . $epod_id . '.eml',
                'dir' => 'EP-I-' . sprintf('%06d', $insert['poradi']) . '-' . $insert['rok'],
                'typ' => '5',
                'popis' => 'Emailová zpráva z epodatelny ' . $insert['poradi'] . '-' . $insert['rok']
                    //'popis'=>'Emailová zpráva'
            );

            if ($file = $UploadFile->uploadEpodatelna($raw_message, $data)) {
                $update_data = ['stav' => 1,
                    'stav_info' => 'Zpráva byla uložena',
                    'file_id' => $file->id
                ];
            } else {
                $update_data = ['stav_info' => 'Originál zprávy se nepodařilo uložit'];
            }
            $this->Epodatelna->update(
                    $update_data, [['id = %i', $epod_id]]
            );

            $messages_recorded++;
        }

        $imap->close();

        return $messages_recorded;
    }

    public static function nactiISDS($storage, $file_id)
    {
        $DownloadFile = $storage;

        if (strpos($file_id, "-") !== false) {
            $file_id = reset(explode("-", $file_id));
        }

        $FileModel = new FileModel();
        $file = $FileModel->getInfo($file_id);
        $res = $DownloadFile->download($file, 1);
        if ($res >= 1) {
            return null;
        } else {

            return $res;
        }
    }

    public function renderIsdsovereni($id)
    {
        $output = "Nemohu najít soubor s datovou zprávou.";
        $epodatelna_id = $id;
        $FileModel = new FileModel();
        $file = $FileModel->select(array(array("nazev = %s", 'ep-isds-' . $epodatelna_id . '.zfo')))->fetch();
        if ($file) {
            // Nacteni originalu DS
            $DownloadFile = $this->storage;
            $source = $DownloadFile->download($file, 1);
            if ($source) {
                $isds = new ISDS_Spisovka();
                try {
                    $isds->pripojit();
                    if ($isds->AuthenticateMessage($source)) {
                        $output = "Datová zpráva je platná.";
                    } else {
                        $output = "Datová zpráva není platná!<br />" .
                                'ISDS zpráva: ' . $isds->error();
                    }
                } catch (Exception $e) {
                    $output = "Nepodařilo se připojit k ISDS schránce!<br />" .
                            'chyba: ' . $e->getMessage();
                }
            }
        }

        $this->sendJson(['id' => 'snippet-isdsovereni', 'html' => $output]);
    }

}
