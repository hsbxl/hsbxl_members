<?php

namespace Drupal\hsbxl_members\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\user\Entity\User;
use Drupal\ldap_servers\ServerFactory;

/**
 * Class MembersImportCommand.
 *
 * @DrupalCommand (
 *     extension="hsbxl_members",
 *     extensionType="module"
 * )
 */
class MembersImportCommand extends ContainerAwareCommand {

  function __construct($name = NULL) {
    $this->serverFactory = new \Drupal\ldap_servers\ServerFactory;
    parent::__construct($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('hsbxl:membersimport')
      ->setDescription($this->trans('commands.hsbxl.membersimport.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $ldapserverfactory = new ServerFactory();
    $provisioningServer = \Drupal::config('ldap_user.settings')->get('drupalAcctProvisionServer');
    $server = $ldapserverfactory->getServerByIdEnabled($provisioningServer);

    if(!$server) {
      $this->getIo()->error('No LDAP server');
      return;
    }

    set_time_limit(10);
    $ldapconn = ldap_connect($server->address) or die("Could not connect to LDAP server.");
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);

    if($ldapconn) {
      $ldapbind = ldap_bind($ldapconn, $server->binddn, $server->bindpw) or die ("Error trying to bind: " . ldap_error($ldapconn));

      if ($ldapbind) {

        $result = ldap_search($ldapconn, $server->basedn, "(&(objectClass=*))") or die ("Error in search query: " . ldap_error($ldapconn));
        $data = ldap_get_entries($ldapconn, $result);

        for ($i=0; $i < $data["count"]; $i++) {

          if(empty($data[$i]["uid"][0])) {
            continue;
          }

          // check if username already exists in drupal.
          if(user_load_by_name($data[$i]["uid"][0])) {
            $this->getIo()->info($data[$i]["uid"][0] . ' was already in Drupal.');
            continue;
          }

          $this->getIo()->info($data[$i]["uid"][0]);

          $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
          $user = User::create();

          //Mandatory settings
          $user->setPassword(random_bytes(10));
          $user->enforceIsNew();
          $user->setEmail($data[$i]["mail"][0]);
          $user->setUsername($data[$i]["uid"][0]);
          $user->set('field_structured_memo', $data[$i]["x-hsbxl-membershipstructcomm"][0]);
          $user->set('field_membership_reason', $data[$i]["description"][0]);
          $user->set('field_telephone_number', $data[$i]["homephone"][0]);

          //$user->set("setting_name", 'setting_value');
          $user->activate();

          //Save user
          $user->save();

        }
      } else {
        $this->getIo()->error('LDAP bind failed...');
      }
    }

    ldap_close($ldapconn);
  }
}
