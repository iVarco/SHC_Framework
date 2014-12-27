<?php

namespace SHC\Command\Smartphone;

//Imports
use RWF\Core\RWF;
use RWF\Request\Commands\PageCommand;
use RWF\Request\Request;
use RWF\User\UserEditor;
use RWF\User\UserGroup;
use RWF\Util\DataTypeUtil;
use RWF\Util\Message;
use SHC\Core\SHC;
use SHC\Form\Forms\UserGroupForm;

/**
 * Zeigt eine Liste mit allen Benutzern an
 *
 * @author     Oliver Kleditzsch
 * @copyright  Copyright (c) 2014, Oliver Kleditzsch
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @since      2.0.0-0
 * @version    2.0.0-0
 */
class EditGroupFormPage extends PageCommand {

    protected $template = 'editgroupform.html';

    protected $premission = 'shc.acp.userManagement';

    /**
     * Sprachpakete die geladen werden sollen
     *
     * @var Array
     */
    protected $languageModules = array('index', 'usermanagement', 'acpindex');

    /**
     * Daten verarbeiten
     */
    public function processData() {

        $tpl = RWF::getTemplate();

        //Headline Daten
        $tpl->assign('apps', SHC::listApps());
        $tpl->assign('acp', true);
        $tpl->assign('style', SHC::getStyle());
        $tpl->assign('user', SHC::getVisitor());
        $tpl->assign('backLink', 'index.php?app=shc&m&page=listgroups');
        $tpl->assign('device', SHC_DETECTED_DEVICE);

        //Gruppen Objekt laden
        $groupId = RWF::getRequest()->getParam('id', Request::GET, DataTypeUtil::INTEGER);
        $group = UserEditor::getInstance()->getUserGroupById($groupId);

        //pruefen ob die Benutzergruppe existiert
        if(!$group instanceof UserGroup) {

            $tpl->assign('message', new Message(Message::ERROR, RWF::getLanguage()->get('acp.userManagement.form.error.id.group')));
            return;
        }

        //Formular erstellen
        $groupForm = new UserGroupForm($group);
        $groupForm->setView(UserGroupForm::SMARTPHONE_VIEW);
        $groupForm->setAction('index.php?app=shc&m&page=editgroupform&id='. $group->getId());
        $groupForm->addId('shc-view-form-editGroup');

        if(!$groupForm->isSubmitted() || ($groupForm->isSubmitted() && !$groupForm->validate() === true)) {

            $tpl->assign('groupForm', $groupForm);
        } else {

            //Speichern
            $name = $groupForm->getElementByName('name')->getValue();
            $description = $groupForm->getElementByName('description')->getValue();
            $premissions = array();
            foreach(UserEditor::getInstance()->getUserGroupById(1)->listPremissions() as $premissionName => $premissionValue) {

                if(preg_match('#^shc\.#', $premissionName)) {

                    $value = $groupForm->getElementByName(str_replace('.', '_', $premissionName))->getValue();
                    $premissions[$premissionName] = ($value === null ? false : $value);
                }
            }

            $message = new Message();
            try {

                UserEditor::getInstance()->editUserGroup($groupId, $name, $description, $premissions);
                $message->setType(Message::SUCCESSFULLY);
                $message->setMessage(RWF::getLanguage()->get('acp.userManagement.form.success.editGroup'));
            } catch(\Exception $e) {

                if($e->getCode() == 1112) {

                    //Gruppenname schon vergeben
                    $message->setType(Message::ERROR);
                    $message->setMessage(RWF::getLanguage()->get('acp.userManagement.form.error.1112.group'));
                }  elseif($e->getCode() == 1102) {

                    //fehlende Schreibrechte
                    $message->setType(Message::ERROR);
                    $message->setMessage(RWF::getLanguage()->get('acp.userManagement.form.error.1102.group'));
                } else {

                    //Allgemeiner Fehler
                    $message->setType(Message::ERROR);
                    $message->setMessage(RWF::getLanguage()->get('acp.userManagement.form.error.group'));
                }
            }
            RWF::getSession()->setMessage($message);

            //Umleiten
            $this->response->addLocationHeader('index.php?app=shc&m&page=listgroups');
            $this->response->setBody('');
            $this->template = '';
        }

    }
}