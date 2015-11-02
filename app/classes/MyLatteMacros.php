<?php

// Pomocna trida pro uzivatelska Latte makra

class MyLatteMacros
{

    public static function vlink($__param, $__link)
    {

        //P.L. zrus tuto funkci, protoze:
        // 1. pristup se kontroluje na cilove adrese odkazu
        // 2. a pristup se kontroluje v hlavni sablone jiz v momente, kdy se rozhoduje, zda link vubec zobrazit
        return $__link;
    }

    // Ignoruj pripadnou polozku view (__array[3] ) v parametru makra, protoze v aplikaci se prideluje pristup pouze na urovni presenteru
    public static function access($user, $param)
    {

        $__array = explode(":", $param);
        $__resource = $__array[1] . "_" . $__array[2] . "Presenter";

        return $user->isAllowed($__resource);
    }

    public static function isAllowed($user, $resource, $privilege)
    {
        return $user->isAllowed($resource, $privilege);
    }

    public static function isInRole($user, $role)
    {
        return $user->isInRole($role);
    }

    public static function CSS($publicUrl, $filename, $media = 'screen')
    {

        // $filename = Nette\Latte\Engine::fetchToken($content); // filename [,] [media]
        // $media = Nette\Latte\Engine::fetchToken($content);

        $filename .= '.css';
        $href = "{$publicUrl}css/$filename?" . @filemtime(dirname(APP_DIR) . "/public/css/$filename");
        $res = "<link rel=\"stylesheet\" type=\"text/css\" media=\"$media\" href=\"$href\" />";

        return $res;
    }

    public static function JavaScript($filename, $publicUrl)
    {

        $filename .= '.js';
        $href = "{$publicUrl}js/$filename?" . @filemtime(dirname(APP_DIR) . "/public/js/$filename");
        $res = "<script type=\"text/javascript\" src=\"$href\"></script>";

        return $res;
    }

    // Vykresli standardni prvek formulare
    public static function input($form, $name)
    {
        $caption = $form[$name]->caption;
        $control = isset($form[$name]->controlPart) ? $form[$name]->controlPart : $form[$name]->control;
        
        $renderer = $form->getRenderer();
        $tpair = $renderer->wrappers['pair']['container'];
        $tlabel = $renderer->wrappers['label']['container'];
        $tcontrol = $renderer->wrappers['control']['container'];
        
        return "<$tpair>
            <$tlabel>$caption</$tlabel>
            <$tcontrol>$control</$tcontrol>
        </$tpair>";
    }

}
