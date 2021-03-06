<?php
// Connection Component Binding
Doctrine_Manager::getInstance()->bindComponent('GcrChatSession', 'doctrine');

/**
 * BaseGcrChatSession
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property integer $time_created
 * @property string $room_id
 * @property string $eschool_id
 * 
 * @method integer        getId()           Returns the current record's "id" value
 * @method integer        getTimeCreated()  Returns the current record's "time_created" value
 * @method string         getRoomId()       Returns the current record's "room_id" value
 * @method string         getEschoolId()    Returns the current record's "eschool_id" value
 * @method GcrChatSession setId()           Sets the current record's "id" value
 * @method GcrChatSession setTimeCreated()  Sets the current record's "time_created" value
 * @method GcrChatSession setRoomId()       Sets the current record's "room_id" value
 * @method GcrChatSession setEschoolId()    Sets the current record's "eschool_id" value
 * 
 * @package    globalclassroom
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class BaseGcrChatSession extends sfDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('gcr_chat_session');
        $this->hasColumn('id', 'integer', 8, array(
             'type' => 'integer',
             'fixed' => 0,
             'unsigned' => false,
             'primary' => true,
             'sequence' => 'gcr_chat_session_id',
             'length' => 8,
             ));
        $this->hasColumn('time_created', 'integer', 4, array(
             'type' => 'integer',
             'fixed' => 0,
             'unsigned' => false,
             'notnull' => true,
             'default' => '0',
             'primary' => false,
             'length' => 4,
             ));
        $this->hasColumn('room_id', 'string', null, array(
             'type' => 'string',
             'fixed' => 0,
             'unsigned' => false,
             'notnull' => true,
             'default' => '',
             'primary' => false,
             'length' => '',
             ));
        $this->hasColumn('eschool_id', 'string', null, array(
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