<?php

/**
 * Eschool
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    globalclassroom
 * @subpackage model
 * @author     Ron Stewart
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */

class GcrEschool extends BaseGcrEschool
{
    public function allowsEclassrooms()
    {
        if (!$this->getConfigVar('gc_classroom_cost_year'))
        {
            if (!$this->getConfigVar('gc_classroom_cost_month'))
            {
                return false;
            }
        }
        return true;
    }
    public function countMdlTableRecords($tableName)
    {
        return GcrDatabaseAccessPostgres::countTableRecords($this, $tableName);
    }
    public function getCategories()
    {
        $categories = array();
        $sql = "SELECT id, name from $this->short_name.mdl_course_categories WHERE visible = ?";
        $params = array(1);
        $results = $this->gcQuery($sql, $params);
        foreach($results as $result)
        {
            $result->eschoolid = $this->getId();
        }
        return $results;
    }
    public function beginTransaction()
    {
        GcrDatabaseAccessPostgres::beginTransaction();
    }
    public function commitTransaction()
    {
        GcrDatabaseAccessPostgres::commitTransaction();
    }
    public function rollbackTransaction()
    {
        GcrDatabaseAccessPostgres::rollbackTransaction();
    }
    public function create()
    {
        if (!$institution = $this->getInstitution())
        {
            global $CFG;
            $CFG->current_app->gcError('Creator institution for new eSchool ' . $this->short_name . ' not found', 'gcdatabaseerror');
        }
        else if (!$mhr_owner = $this->getInstitution()->getOwnerUser())
        {
            global $CFG;
            $CFG->current_app->gcError('Institution owner of ' . $institution->getShortName() . ' not found', 'gcdatabaseerror');
        }
        GcrDatabaseAccessPostgres::createSchema($this);

        $eschool = $this->getAppType()->getTemplateObject();
        // add the template's salt to the list of historical salts for the new eschool
        $salt_history = new GcrEschoolSaltHistory();
        $salt_history->setEschoolid($this->id);
        $salt_history->setSalt($eschool->password_salt);
        $salt_history->save();

        // transfer all old salts from template to new eschool
        if ($old_salts = Doctrine::getTable('GcrEschoolSaltHistory')->findByEschoolid($eschool->id))
        {
            foreach($old_salts as $salt_record)
            {
                $salt_history = new GcrEschoolSaltHistory();
                $salt_history->setEschoolid($this->id);
                $salt_history->setSalt($salt_record->salt);
                $salt_history->save();
            }
        }

        // change the title name of the eschool from Eschool Template to whatever its called
        $this->updateMdlTable('course', array('fullname' => $this->full_name, 'shortname' => $this->short_name), array('id' => 1));
        // change the self-reference entry in mdl_mnet_host to the new eschool's info
        $this->updateMdlTable('mnet_host', array('name' => $this->full_name), array('wwwroot' => $this->getAppUrl()));
        // change the gcadmin username and password to the generated one.
        $password = md5($this->admin_password);
        $this->updateMdlTable('user', array('password' => $password), array('username' => "gc4{$this->short_name}admin"));
        // Change the web services token for gc4<schema>admin to a new uniue value.
        $this->updateMdlTable('external_tokens', array('token' => md5(uniqid(rand(),1))),
                    array('userid' => $this->getGCAdminUser()->getObject()->id));

        $this->setMnetConnection();
        
        // Remove mnet connection to template's institution (if it exists)
        $template_institution = $eschool->getInstitution();
        if ($template_institution)
        {
            $this->removeMnetConnection($template_institution);
        }
        $this->setUser($mhr_owner, array('eschooladmin'));

        // set unique mdl_config vars
        $this->setConfigVar('calendar_exportsalt', GcrEschoolTable::generateRandomString(60));
        $this->setConfigVar('alternateloginurl', $this->getUrl() . '/eschool/login');
        $this->setConfigVar('resource_secretphrase', GcrEschoolTable::generateRandomString(60));
        $this->setConfigVar('calendar_exportsalt', GcrEschoolTable::generateRandomString(20));
        $this->setConfigVar('siteidentifier', GcrEschoolTable::generateRandomString(32) . $this->getDomain());
        $this->setConfigVar('cronremotepassword', GcrEschoolTable::generateRandomString(20));
        $this->setConfigVar('noreplyaddress', $this->getDomain());
        $this->setConfigVar('registerauth', 'email');
        $this->setMdlCacheSettings();
        $institution->createMnetConnection($this);
    }

    public function deleteFromMdlTable($tableName, $columnName, $columnValue)
    {
        GcrDatabaseAccessPostgres::deleteFromTable($this, $tableName, $columnName, $columnValue);
    }

    // VERY POWERFUL FUNCTION, CALL WITH CARE! This will delete this eschool from our system entirely
    // (like it never existed).
    public function deleteEschoolFromSystemEntirely()
    {
        // remove all mnet connections with any maharas
        if ($this->isCreated())
        {
            foreach ($this->getMnetInstitutions() as $institution)
            {
                $institution->removeMnetConnection($this);
            }
        }
        GcrDatabaseAccessPostgres::deleteSchemaFromSystem($this);
        
        // delete moodledata directory for eschool
        exec(escapeshellcmd('rm -R ' . gcr::moodledataDir . $this->short_name . '/'));
        // delete global eschool row for this eschool and related address and person table rows
        
        GcrCommissionTable::getInstance()->createQuery('c')
                ->delete()
                ->where('c.eschool_id = ?', $this->short_name)
                ->execute();
        GcrEclassroomTable::getInstance()->createQuery('e')
                ->delete()
                ->where('e.eschool_id = ?', $this->short_name)
                ->execute();
       // delete password salt history for this eschool
        $q = Doctrine_Query::create()
            ->delete('GcrEschoolSaltHistory')
            ->where('eschoolid = ?', $this->id);
        $q->execute();
        
        if ($address = $this->getAddressObject())
        {
            $address->delete();
        }
        if ($contact1 = $this->getPersonObject())
        {
            $contact1->delete();
        }
        if ($contact2 = $this->getPerson2Object())
        {
            $contact2->delete();
        }
        $this->delete();
    }
    public function deleteCacheDirectories()
    {
        $output = '';
        $command = 'rm -rf /opt/moodledata/globalclassroom4/' . $this->short_name . '/cache/*';
        system($command, $output);
        return ($output == 0);
    }
    public function gcQuery($sql, $params = array(), $returnOneRecord = false, $failSilently = false)
    {
        return GcrDatabaseAccessPostgres::gcQuery($this, $sql, $params, $returnOneRecord, $failSilently);
    }

    public function getActiveTrial()
    {
        return $this->getInstitution()->getActiveTrial();
    }
    public function getAllowedUsers($institution = false)
    {
        $mnet_host_id = false;
        if ($institution)
        {
            $mnet_host_id = $this->getMnetHostId($institution);
        }
        //gets the user who is allowed to network and is not the owner user
        $owner_user = $this->getOwnerUser();
        $sql = 'SELECT u.* FROM ' . $this->getShortName() . '.mdl_user u, ' .
            $this->getShortName() . '.mdl_mnet_sso_access_control mnet ' .
            'WHERE u.username = mnet.username AND mnet.accessctrl = ? AND u.id != ?';
        $params = array('allow', $owner_user->getObject()->id);
        if ($mnet_host_id)
        {
            $sql .= ' AND u.mnethostid = ?';
            $params[] = $mnet_host_id;
        }
        return $this->gcQuery($sql, $params);
    }
    public function getAddressObject()
    {
        return Doctrine::getTable('GcrAddress')->find($this->address);
    }
    public function getClassroomTrialLength()
    {
        if ($config_var = $this->getConfigVar('gc_classroom_trial_length'))
        {
            $length = $config_var;
        }
        else
        {
            $length = gcr::classroomTrialLengthInDays;
        }
        return $length;
    }
    public function getConfigVar($var_name)
    {
        if($var = $this->selectFromMdlTable('config', 'name', $var_name, true))
        {
            return $var->value;
        }
        return false;
    }
    public function getConnection()
    {
        return GcrDatabaseAccessPostgres::getConnection();
    }
    public function getCourse($id)
    {
        $mdl_course = $this->selectFromMdlTable('course', 'id', $id, true);
        if ($mdl_course)
        {
            return new GcrMdlCourse($mdl_course, $this);
        }
        return false;
    }
    public function getCourseCategories($include_course_collections = true)
    {
        $course_categories = array();
        if ($include_course_collections)
        {
            $mdl_course_categories = $this->selectFromMdlTable('course_categories');
        }
        else
        {
            $sql = 'SELECT * FROM ' . $this->short_name . 
                    '.mdl_course_categories where idnumber IS NULL OR idnumber = ?';
            $mdl_course_categories = $this->gcQuery($sql, array(''));
        }
        foreach($mdl_course_categories as $mdl_course_category)
        {
            $course_categories[] = new GcrMdlCourseCategory($mdl_course_category, $this);
        }
        return $course_categories;
    }
    public function getCourseCollections($parent_category_id = false)
    {
        $course_collections = array();
        $sql = 'SELECT * FROM ' . $this->short_name . 
                '.mdl_course_categories where ';
        $params = array();
        if ($parent_category_id)
        {
            $sql .= 'parent = ? AND ';
            $params[] = $parent_category_id;
        }
        $sql .= '(idnumber IS NOT NULL OR idnumber <> ?)';
        $params[] = '';
        $mdl_course_categories = $this->gcQuery($sql, $params);
        foreach($mdl_course_categories as $mdl_course_category)
        {
            $course_category = new GcrMdlCourseCategory($mdl_course_category, $this);
            $course_collections[] = GcrCourseCollection::getInstance($course_category);
        }
        return $course_collections;
    }
    public function getMdlCourses($visible = false, $search_string = false, $category_id = false)
    {
        $params = array();
        $sql = 'select c.* from ' . $this->short_name . '.mdl_course c';
        $where = array('c.id > 1');
        if ($visible !== false)
        {
            $where[] = 'c.visible = ?';
            $params[] = $visible; 
        }
        if ($search_string !== false)
        {
            $where[] = "(c.fullname ILIKE '%' || ? || '%' OR
                    c.shortname ILIKE '%' || ? || '%' OR
                    c.summary ILIKE '%' || ? || '%')";
            $params = array_pad($params, count($params) + 3, $search_string);
        }
        if ($category_id !== false)
        {
            $where[] = 'c.category = ?';
            $params[] = $category_id;
        }
        if (count($where) > 0)
        {
            $first_item = true;
            foreach($where as $condition)
            {
                $sql .= ($first_item) ? ' where ' : ' and ';
                $sql .= $condition;
                $first_item = false;
            }
        }
        return $this->gcQuery($sql . ' order by c.fullname', $params);
    }
    public function getMdlEnrolments() {
        return $this->selectFromMdlTable('enrol');
    }
    public function getCourseCount()
    {
        return $this->countMdlTableRecords('course') - 1;
    }
    public function getDatabaseTablePrefix()
    {
        return gcr::moodlePrefix;
    }
    public function getDomain()
    {
        return $this->short_name . '.' . gcr::domainName;
    }
    public function getEclassroom($mhr_user)
    {
        $eclassroom = Doctrine::getTable('GcrEclassroom')->createQuery('e')
            ->where('e.eschool_id = ?', $this->short_name)
            ->andWhere('e.user_id = ?', $mhr_user->getObject()->id)
            ->orderBy('e.trans_time')
            ->fetchOne();
        return $eclassroom;
    }
    public function getEclassroomUsers()
    {
        return $this->institution->getEclassroomUsers($this);
    }
    public function getGCAdminUser()
    {
        $mdl_user_obj = $this->selectFromMdlTable('user', 'username', 'gc4' . $this->short_name . 'admin', true);
        return new GcrMdlUser($mdl_user_obj, $this);
    }
    public function getGcFeeClassroom()
    {
        if ($gc_fee = $this->selectFromMdlTable('config', 'name', 'gc_classroom_fee_percent', true))
        {
            return $gc_fee->value;
        }
        else
        {
            return 0;
        }
    }
    public function getGcFeeCourse()
    {
        if ($gc_fee = $this->selectFromMdlTable('config', 'name', 'gc_course_fee_percent', true))
        {
            return $gc_fee->value;
        }
        else
        {
            return 0;
        }
    }
    public function getInstitution()
    {
        return Doctrine::getTable('GcrInstitution')->find($this->organization_id);
    }
    public function getInstitutionJumpUrl($wantsurl = false, $institution = false)
    {
        if (!$institution)
        {
            $institution = $this->getInstitution();
        }
        if ($mhr_auth_instance = $institution->getAuthInstance($this))
        {
            $url = $institution->getAppUrl() . 'auth/xmlrpc/jump.php?wr=' .
                    $this->getAppUrl() . '&ins=' . $mhr_auth_instance->id;
            if ($wantsurl && strlen($wantsurl) > 1)
            {
                $wantsurl = str_replace($this->getUrl(), '', $wantsurl);
                $gcr_wants_url = GcrWantsUrlTable::createWantsUrl('simple', $this, $wantsurl);
                $url .= '&wantsurl=/custom/frontend.php%3Furl=' . $gcr_wants_url->getId();
            }
            return $url;
        }
        return false;
    }
    public function getLastActivityTimestamp($include_gc_admin = false)
    {
        if ($include_gc_admin)
        {
            $record = $this->selectFromMdlTable('log', null, null, true, 'time DESC');
        }
        else
        {
            if (!$gc_admin = $this->getGCAdminUser())
            {
                global $CFG;
                $CFG->current_app->gcError("eSchool $this->short_name: No gcadminuser found.");
                $gc_admin = new stdClass;
                $gc_admin->id = -1;
            }
            $sql = 'select max(time) as time from ' . $this->short_name . '.mdl_log where userid != ? order by time DESC';
            $record = $this->gcQuery($sql, array($gc_admin->getObject()->id), true);
        }
        return $record->time;
    }
    public function getLogoFilePath()
    {
        return gcr::moodledataDir . $this->short_name . '/gc_images/' . $this->logo;
    }
    public function getGradeLetters($mdl_context = false)
    {
        if (!$mdl_context)
        {
            $mdl_context = $this->selectFromMdlTable('context', 'id', 1, true);
        }
        $mdl_grade_letters = $this->selectFromMdlTable('grade_letters', 'contextid', $mdl_context->id);
        if (count($mdl_grade_letters) < 1)
        {
            $parent_context_ids = $this->getParentContexts($mdl_context);
            foreach ($parent_context_ids as $parent_context_id)
            {
                if (count($mdl_grade_letters) > 1)
                {
                    continue;
                }
                $mdl_grade_letters = $this->selectFromMdlTable('grade_letters', 'contextid', $parent_context_id);
            }
        }
        if (count($mdl_grade_letters) < 1)
        {
            //default grading letters
            return array('93'=>'A', '90'=>'A-', '87'=>'B+', '83'=>'B', '80'=>'B-', '77'=>'C+', '73'=>'C', '70'=>'C-', '67'=>'D+', '60'=>'D', '0'=>'F');
        }
        $grade_array = array();
        foreach ($mdl_grade_letters as $mdl_grade_letter)
        {
            $grade_array[$mdl_grade_letter->lowerboundary] = $mdl_grade_letter->letter;
        }
        return $grade_array;
    }
    public function getMdlRoleAssignments($role_name, $context_id, $user_id = false)        
    {
        $mdl_role = $this->selectFromMdlTable('role', 'shortname', $role_name, true);
        
        $sql = 'select * from ' . $this->short_name . 
                '.mdl_role_assignments where roleid = ? and contextid = ?';
            
        $params = array($mdl_role->id, $context_id);
        if ($user_id)
        {
            $params[] = $user_id;
            $sql .=  ' and userid = ?';
        }
        
        $role_assignments = $this->gcQuery($sql, $params);
        if (count($role_assignments) > 0)
        {
            return $role_assignments;
        }
        return false;
    }
    public function setMdlRoleAssignment($role_name, $context_id, $user_id)
    {
        $mdl_role = $this->selectFromMdlTable('role', 'shortname', $role_name, true);
        if ($mdl_role)
        {
            $existing_role_assignment = $this->getMdlRoleAssignments($role_name, $context_id, $user_id);
            if (!$existing_role_assignment)
            {
                $params = array('roleid' => $mdl_role->id,
                                'contextid' => $context_id,
                                'userid' => $user_id);
                
                $rec = $this->insertIntoMdlTable('role_assignments', $params);
            }
        }
    }
    public function getMnetHost($institution)
    {
        return $this->selectFromMdlTable('mnet_host', 'wwwroot', $institution->getAppUrl(false), true);
    }
    public function getMnetHostById($id)
    {
        return $this->selectFromMdlTable('mnet_host', 'id', $id, true);
    }
    public function getMnetHostId($institution)
    {
        if ($mdl_mnet_host = $this->getMnetHost($institution))
        {
            return $mdl_mnet_host->id;
        }
        return false;
    }
    public function getSelfMdlMnetHostRecord()
    {
        if (!$host = $this->selectFromMdlTable('mnet_host', 'wwwroot', $this->getAppUrl(), true))
        {
            global $CFG;
            $CFG->current_app->gcError('eSchool ' . $this->short_name . ' has no mnet_host self-reference.', 'gcdatabaseerror');
        }
        return $this->selectFromMdlTable('mnet_host', 'id', $host->id, true);
    }
    public function getOwnerFeeCourse()
    {
        if ($owner_fee = $this->selectFromMdlTable('config', 'name', 'owner_course_fee_percent', true))
        {
            return $owner_fee->value;
        }
        else
        {
            return 0;
        }
    }
    public function getMnetInstitution($host_id)
    {
        if ($mdl_mnet_host = $this->selectFromMdlTable('mnet_host', 'id', $host_id, true))
        {
            return $this->getHost($mdl_mnet_host);
        }
    }
    // This returns all of the hosts listed in this eschool's mdl_mnet_host table
    // where the record corresponds to an existing GcrInstitution record.
    public function getMnetInstitutions()
    {
        $mnet_institutions = array();
        $filters = array(new GcrDatabaseQueryFilter('deleted', '=', '0'));
        $q = new GcrDatabaseQuery($this, 'mnet_host', 'select * from', $filters);
        foreach ($q->executeQuery() as $mdl_mnet_host)
        {
            if ($institution = $this->getHost($mdl_mnet_host))
            {
                $mnet_institutions[] = $institution;
            }
        }
        if (count($mnet_institutions) > 0)
        {
            return $mnet_institutions;
        }
        return false;
    }
    public function getAppUrl($path = false)
    {
        if (!$path)
        {
            $path = '';
        }
        return 'https://' . $this->short_name . '.' . gcr::moodleDomain . $path;
    }
    public function getOwnerUser()
    {
        return $this->getUser($this->getInstitution()->getOwnerUser());
    }
    public function getPurchases($type = false, $start_ts = 0, $end_ts = null,
            $include_all_recurring = false, $include_manual = true)
    {
        return GcrPurchaseTable::getAppPurchases($this, $type, $start_ts, $end_ts,
                $include_all_recurring, $include_manual);
    }
    public function getParentContexts($mdl_context, $include_self = false)
    {
        if ($mdl_context->path == '')
        {
            return array();
        }

        $parent_contexts = substr($mdl_context->path, 1); // kill leading slash
        $parent_contexts = explode('/', $parent_contexts);
        if (!$include_self)
        {
            array_pop($parent_contexts); // and remove its own id
        }

        return array_reverse($parent_contexts);

    }
    public function getOwnerUserLocal()
    {
        if ($mhr_user = $this->getOwnerUser())
        {
            return $this->getUser($mhr_user);
        }
        return false;
    }
    public function getHost($mdl_mnet_host)
    {
        if ($short_name = GcrEschoolTable::parseShortNameFromUrl($mdl_mnet_host->wwwroot))
        {
            if ($institution = Doctrine::getTable('GcrInstitution')->findOneByShortName($short_name))
            {
                return $institution;
            }
        }
        return false;
    }
    public function getPersonObject()
    {
        return Doctrine::getTable('GcrPerson')->find($this->contact1);
    }
    public function getPerson2Object()
    {
        return Doctrine::getTable('GcrPerson')->find($this->contact2);
    }
    public function getTableName()
    {
        return 'GcrEschool';
    }
    public function getSupportUrl()
    {
        return $this->getInstitution()->getSupportUrl();
    }
    public function getUserOnInstitutionFromId($user_id)
    {
        if ($mdl_user_obj = $this->selectFromMdlTable('user', 'id', $user_id, true))
        {
            $mdl_user = new GcrMdlUser($mdl_user_obj, $this);
            return $mdl_user->getUserOnInstitution();
        }
        return false;
    }
    public function getUser($mhr_user)
    {
        $mnet_host_id = $this->getMnetHostId($mhr_user->getApp());
        if (!empty($mnet_host_id)) {
            $sql = 'SELECT * FROM ' . $this->short_name . '.mdl_user WHERE username = ? AND mnethostid = ?';
            if ($mdl_user = $this->gcQuery($sql, array($mhr_user->getObject()->username, $mnet_host_id), true))
            {
                return new GcrMdlUser($mdl_user, $this);
            }
        }
        return false;
    }
    public function getUserById($id)
    {
        $mdl_user = $this->selectFromMdlTable('user', 'id', $id, true);
        if ($mdl_user)
        {
            return new GcrMdlUser($mdl_user, $this);
        }
        return false;
    }
    public function getAppType()
    {
        return Doctrine::getTable('GcrEschoolType')->find($this->getEschoolType());
    }
    public function getUrl($path = null)
    {
        return 'https://' . $this->short_name . '.' . gcr::domainName . $path;
    }
    public function getUserCount()
    {
        return $this->countMdlTableRecords('user');
    }
    public function getWebServicesToken()
    {
        $gc_admin_user = $this->getGCAdminUser();
        if ($mdl_token = $this->selectFromMdlTable('external_tokens', 'userid', $gc_admin_user->getObject()->id, true))
        {
            return $mdl_token->token;
        }
        return false;

    }
    public function hasMnetConnection(GcrInstitution $institution)
    {
        $id = $institution->getId();
        foreach ($this->getMnetInstitutions() as $mnet_institution)
        {
            if ($mnet_institution->getId() == $id)
            {
                return true;
            }
        }
        return false;
    }
    public function insertIntoMdlTable($tableName, $valueArray)
    {
        return GcrDatabaseAccessPostgres::insertIntoTable($this, $tableName, $valueArray);
    }
    public function isClassroomAllowed($billing_cycle)
    {
        $billing_cycle = strtolower($billing_cycle);
        if ($this->getConfigVar('gc_classroom_cost_' . $billing_cycle))
        {
            return true;
        }
        return false;
    }
    public function getDefaultEschoolInstitution()
    {
        return Doctrine::getTable('GcrInstitution')->findOneByDefaultEschoolId($this->short_name);
    }
    public function isCreated()
    {
        return GcrDatabaseAccessPostgres::schemaExists($this);
    }
    public function isHome()
    {
        return ($this->short_name == gcr::gchomeSchemaMoodle);
    }
    public function isInternal()
    {
        return $this->getInstitution()->isInternal();
    }
    public function isMoodle()
    {
        return true;
    }
    public function isMahara()
    {
        return false;
    }
    public function isTemplate()
    {
        if (Doctrine::getTable('GcrEschoolType')->findOneByTemplate($this->short_name))
        {
            return true;
        }
        return false;
    }
    public function isPrimaryTemplate()
    {
        return ($this->short_name == gcr::gcPrimaryMoodleTemplate);
    }
    public function isUserGuest()
    {
        global $CFG;
        $current_user = $CFG->current_app->getCurrentUser();
        $mdl_user = $current_user->getObject();
        if($mdl_user->id == 1 && $mdl_user->mnethostid == 1 && $mdl_user->username == 'guest')
        {
            return true;
        }
        return false;
    }
    public function isUserGCAdmin($user = false)
    {
        if ($user)
        {
            $mdl_user = $user->getObject();
        }
        else
        {
            $current_user = new GCUser();
            $mdl_user = $current_user->getObject();
        }
        global $CFG;
        $sql = "SELECT SUM(rc.permission)
                FROM $this->short_name.mdl_role_capabilities rc
                JOIN $this->short_name.mdl_context ctx
                  ON ctx.id=rc.contextid
                JOIN $this->short_name.mdl_role_assignments ra
                  ON ra.roleid=rc.roleid AND ra.contextid=ctx.id
                WHERE ctx.contextlevel=10
                  AND ra.userid = ?
                  AND rc.capability IN ('moodle/site:config', 'moodle/legacy:admin', 'moodle/site:doanything')
                GROUP BY rc.capability
                HAVING SUM(rc.permission) > 0";
        if ($this->gcQuery($sql, array($mdl_user->id), true))
        {
            return true;
        }
        return false;
    }
    public function removeMnetConnection($institution)
    {
        $this->updateMdlTable('mnet_host', array('deleted' => 1), array('wwwroot' => $institution->getAppUrl(false)));
    }
    public function selectFromMdlTable($tableName, $columnName = false, $columnValue = false, $returnOne = false, $orderBy = false)
    {
        return GcrDatabaseAccessPostgres::selectFromTable($this, $tableName, $columnName, $columnValue, $returnOne, $orderBy);
    }
    public function sendMessage($to_mdl_user_obj, $from_mdl_user_obj, $subject, $body_text, $body_html)
    {
         $to_user = new GcrMdlUser($to_mdl_user_obj, $this);
         $from_user = new GcrMdlUser($from_mdl_user_obj, $this);        
         return $to_user->addMessageToInbox($from_user, $subject, $body_text, $body_html);
    }
    public function setConfigVar($var_name, $var_value)
    {
        if ($this->selectFromMdlTable('config', 'name', $var_name))
        {
            $this->updateMdlTable('config', array('value' => $var_value), array('name' => $var_name));
        }
        else
        {
            $this->insertIntoMdlTable('config', array('name' => $var_name, 'value' => $var_value));
        }
    }
    // This function replaces the current Moodle's cache settings config file (located
    // in Moodledata) with the current settings for homeadmin. The siteidentifier is
    // then generated as Moodle would normally do, and the memcached prefix is also 
    // replaced.
    public function setMdlCacheSettings()
    {
        $dataroot = gcr::moodledataDir;
        $home = GcrEschoolTable::getHome();
        $home_short_name = $home->getShortName();
        $home_config_file = $dataroot . $home_short_name . '/muc/config.php';
        $home_site_indentifier = md5((string)$home->getConfigVar('siteidentifier'));
        $site_identifier = md5((string)$this->getConfigVar('siteidentifier'));
        $memcached_prefix = $this->id;
        $config_file = $dataroot . $this->short_name . '/muc/config.php';

        exec('cp ' . $home_config_file . ' ' . $config_file);
        exec("sed 's/{$home_short_name}/{$memcached_prefix}/g' {$config_file} > {$config_file}_tmp");
        exec("sed 's/{$home_site_indentifier}/{$site_identifier}/g' {$config_file}_tmp > {$config_file}");
        exec('rm ' . $config_file . '_tmp');
        $this->deleteCacheDirectories();
    }
    public function setMnetConnection($institution = false)
    {
        if (!$institution)
        {
            $institution = $this->getInstitution();
        }
        $mnet_host = $institution->getMnetData();
        $params = array('deleted' => 0,
                        'wwwroot' => $mnet_host->wwwroot,
                        'name' => $mnet_host->name,
                        'public_key' => $mnet_host->public_key,
                        'public_key_expires' => $mnet_host->keypair_expires);
        if (!$host_id = $this->getMnetHostId($institution))
        {
            $params['ip_address'] = $this->getSelfMdlMnetHostRecord()->ip_address;
            $params['applicationid'] = $this->selectFromMdlTable('mnet_application', 'name', 'mahara', true)->id;
            $this->insertIntoMdlTable('mnet_host', $params);
            $host_id = $this->getMnetHostId($institution);
            $params = array('hostid' => $host_id,
                            'serviceid' => 1,
                            'publish' => 0,
                            'subscribe' => 1);
            // sso_idp, only subscribe
            $this->insertIntoMdlTable('mnet_host2service', $params);
            $params['serviceid'] = 2;
            $params['publish'] = 1;
            $params['subscribe'] = 0;
            // sso_sp, only publish
            $this->insertIntoMdlTable('mnet_host2service', $params);
            $params['serviceid'] = 3;
            // mnet_enrol, only publish
            $this->insertIntoMdlTable('mnet_host2service', $params);
            // no portfolio services (serviceid=4)
        }
        else
        {
            $mdl_mnet_host = $this->selectFromMdlTable('mnet_host', 'id', $host_id, true);
            $this->updateMdlTable('mnet_host', $params, array('wwwroot' => $mdl_mnet_host->wwwroot));
        }
    }
    // This function returns a URL which can be used to auto-login as the gcadmin Administrator
    public function setupAdminAutoLogin()
    {
        $token = GcrInstitutionTable::generateAutoLoginRecord($this->short_name, 'gc4' . $this->short_name .
                'admin', $this->admin_password);
        return $this->getAppUrl() . '/login/index.php?token=' . $token;
    }
    public function setAppTitle($title)
    {
        $this->updateMdlTable('course', array('fullname' => $title), array('id' => 1));
    }
    public function setDeletedUsersOnInstitutionAsDeleted()
    {
        $mdl_users = $this->selectFromMdlTable('user');
        foreach ($mdl_users as $mdl_user_obj)
        {
            $mdl_user = new GcrMdlUser($mdl_user_obj, $this);
            $mhr_user = $mdl_user->getUserOnInstitution();
            if ($mhr_user)
            {
                if ($mhr_user->getObject()->deleted == 1)
                {
                    $this->updateMdlTable('user', array('deleted' => 1), array('id' => $mdl_user_obj->id));
                }
            }
        }   
    }
    public function setUser($mhr_user, $system_roles = array())
    {
        if (!$host_id = $this->getMnetHostId($mhr_user->getApp()))
        {
            global $CFG;
            $CFG->current_app->gcError('User from unknown host ' .
                    $mhr_user->getApp()->getShortName() . ', cannot add.', 'gcdatabaseerror');
        }
        
        $mhr_user_object = $mhr_user->getObject();
        if (!$mdl_user = $this->getUser($mhr_user))
        {
            $params = array('auth' => 'mnet',
                            'confirmed' => 1,
                            'mnethostid' => $host_id,
                            'username' => $mhr_user_object->username,
                            'firstname' => $mhr_user_object->firstname,
                            'lastname' => $mhr_user_object->lastname,
                            'email' => $mhr_user_object->email);
            $mdl_user_object = $this->insertIntoMdlTable('user', $params);
            $mdl_user = new GcrMdlUser($mdl_user_object, $this);
        }
        foreach ($system_roles as $system_role)
        {
            $this->setMdlRoleAssignment($system_role, 1, $mdl_user->getObject()->id);
        }
        return $mdl_user;
    }
    public function updateInstitutionMnetConnections()
    {
        foreach ($this->getMnetInstitutions() as $mnet_institution)
        {
            $mnet_institution->setMnetConnection($this);
        }
    }
    public function updateMdlTable($tableName, $valueAssocArray, $whereAssocArray)
    {
        return GcrDatabaseAccessPostgres::updateTable($this, $tableName, $valueAssocArray, $whereAssocArray);
    }
    public function upsertIntoMdlTable($tableName, $valueAssocArray, $whereAssocArray)
    {
        return GcrDatabaseAccessPostgres::upsertIntoTable($this, $tableName, $valueAssocArray, $whereAssocArray);
    }
    public function addGcrEnrollment($mdl_course, $cost = '')
    {
        $params = array(
            'enrol' => 'globalclassroom',
            'status' => 0,
            'courseid' => $mdl_course->getObject()->id,
            'cost' => $cost,
            'currency' => 'USD',
            'roleid' => 5,
            'timecreated' => time(),
            'timemodified' => time(),
        );
        return $this->insertIntoMdlTable('enrol', $params);
    }
    public function alterGcrEnrollment($mdl_course, $cost = '', $password = '')
    {
        $this->updateMdlTable('enrol', array('cost' => $cost, 'password' => $password),
            array('courseid' => $mdl_course->getObject()->id, 'enrol' => 'globalclassroom'));
    }
    public function alterGcrEnrollmentByName($mdl_course, $name, $value)
    {
        $this->updateMdlTable('enrol', array($name => $value),
                array('courseid' => $mdl_course->getObject()->id, 'enrol' => 'globalclassroom'));
    }
}