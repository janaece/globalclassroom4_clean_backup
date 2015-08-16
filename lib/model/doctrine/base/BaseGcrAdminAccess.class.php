<?php
// Connection Component Binding
Doctrine_Manager::getInstance()->bindComponent('GcrAdminAccess', 'doctrine');

/**
 * BaseGcrAdminAccess
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property integer $userid
 * @property string $username
 * 
 * @method integer        getId()       Returns the current record's "id" value
 * @method integer        getUserid()   Returns the current record's "userid" value
 * @method string         getUsername() Returns the current record's "username" value
 * @method GcrAdminAccess setId()       Sets the current record's "id" value
 * @method GcrAdminAccess setUserid()   Sets the current record's "userid" value
 * @method GcrAdminAccess setUsername() Sets the current record's "username" value
 * 
 * @package    globalclassroom
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class BaseGcrAdminAccess extends sfDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('gcr_admin_access');
        $this->hasColumn('id', 'integer', 8, array(
             'type' => 'integer',
             'fixed' => 0,
             'unsigned' => false,
             'primary' => true,
             'sequence' => 'gcr_admin_access_id',
             'length' => 8,
             ));
        $this->hasColumn('userid', 'integer', 4, array(
             'type' => 'integer',
             'fixed' => 0,
             'unsigned' => false,
             'notnull' => true,
             'default' => '0',
             'primary' => false,
             'length' => 4,
             ));
        $this->hasColumn('username', 'string', null, array(
             'type' => 'string',
             'fixed' => 0,
             'unsigned' => false,
             'notnull' => true,
             'default' => '',
             'primary' => false,
             'length' => '',
             ));
    }

    public function setUp()
    {
        parent::setUp();
        
    }
}