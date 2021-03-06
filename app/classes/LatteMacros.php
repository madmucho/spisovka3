<?php

namespace Spisovka;

/** Pomocna trida pro uzivatelska Latte makra
 */
class LatteMacros
{

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

    public static function CSS($publicUrl, $filename, $media = 'screen, print')
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

    /**
     * Zavola DefaultFormRenderer pro vykresleni paru label/control.
     * Formular musi pouzivat vychozi renderer.
     * @param Form $form
     * @param string $name
     * @return string 
     */
    public static function input2($form, $name)
    {
        $renderer = $form->getRenderer();
        return $renderer->renderPair($form[$name]);

        /* Toto byl muj starsi kod:
          $label = $form[$name]->getLabel();
          $control = isset($form[$name]->controlPart) ? $form[$name]->controlPart : $form[$name]->control;

          $renderer = $form->getRenderer();
          $tpair = $renderer->wrappers['pair']['container'];
          $tlabel = $renderer->wrappers['label']['container'];
          $tcontrol = $renderer->wrappers['control']['container'];
          $tdesc = $renderer->wrappers['control']['description'];

          $description = $form[$name]->getOption('description');
          if (!empty($description))
          $description = " <$tdesc>$description</$tdesc>";

          return "<$tpair>
          <$tlabel>$label</$tlabel>
          <$tcontrol>$control$description</$tcontrol>
          </$tpair>"; */
    }

    /**
     * Zavola DefaultFormRenderer pro vykresleni label.
     * Formular musi pouzivat vychozi renderer.
     * @param Form $form
     * @param string $name
     * @return string 
     */
    public static function label2($form, $name)
    {
        $renderer = $form->getRenderer();
        $control = $form[$name];
        if ($control->isRequired()) {
            $control->getLabelPrototype()->class('required', TRUE);
        }
        $html = $renderer->renderLabel($control);
        // odstran zapouzdrujici element
        return $html->getHtml();
    }

    public static function inputError2($form, $name)
    {
        $renderer = $form->getRenderer();
        return $renderer->renderErrors($form[$name]);
    }

}
