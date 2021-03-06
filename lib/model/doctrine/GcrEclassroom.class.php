<?php

/**
 * GcrEclassroom
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    globalclassroom
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class GcrEclassroom extends BaseGcrEclassroom
{
    public function getCourses()
    {
        $courses = array();
        $mdl_role_assignments = $this->getMdlRoleAssignments();
        foreach ($mdl_role_assignments as $mdl_role_assignment)
        {
            $eschool = $this->getEschool();
            $mdl_context = $eschool->selectFromMdlTable('context', 'id', $mdl_role_assignment->contextid, true);
            if ($mdl_context)
            {
                $mdl_course = $eschool->selectFromMdlTable('course', 'id', $mdl_context->instanceid, true);
                if ($mdl_course)
                {
                    $courses[] = new GcrMdlCourse($mdl_course, $eschool);
                }
            }
        }
        return $courses;
    }
    public function getMdlRoleAssignments()
    {
        $records = array();
        $eschool = $this->getEschool();
        if ($eschool)
        {
            $mhr_user = $this->getUser();
            $mdl_user = $mhr_user->getUserOnEschool($eschool);
            if ($mdl_user)
            {
                $mdl_role = $eschool->selectFromMdlTable('role', 'shortname', 'eclassroomcourseowner', true);
                $filters = array();
                $filters[] = new GcrDatabaseQueryFilter('roleid', '=', $mdl_role->id);
                $filters[] = new GcrDatabaseQueryFilter('userid', '=', $mdl_user->getObject()->id);
                $q = new GcrDatabaseQuery($eschool, 'role_assignments', 'select * from', $filters);
                $records = $q->executeQuery();
            }
        }
        return $records;
    }
    public function getMhrInstitution()
    {
        if ($this->mhr_institution_name != '')
        {
            $institution = $this->getInstitution();
            return $institution->selectFromMhrTable('institution', 'name', $this->mhr_institution_name, true);
        }
        return false;
    }
    public function getCoursesCount()
    {
        return count($this->getMdlRoleAssignments());
    }
    public function getEschool()
    {
        return Doctrine::getTable('GcrEschool')->findOneByShortName($this->eschool_id);
    }
    public function getPurchase()
    {
        $purchase = Doctrine::getTable('GcrPurchase')
                ->createQuery('p')
                ->where('p.user_institution_id = ?', $this->user_institution_id)
                ->andWhere('p.user_id = ?', $this->user_id)
                ->andWhere('p.purchase_type = ?', 'classroom')
                ->andWhere('p.purchase_type_eschool_id = ?', $this->eschool_id)
                ->fetchOne();
        return $purchase;
    }
    public function getManualPurchases()
    {
        $purchases = Doctrine::getTable('GcrPurchase')
                ->createQuery('p')
                ->where('p.user_institution_id = ?', $this->user_institution_id)
                ->andWhere('p.user_id = ?', $this->user_id)
                ->andWhere('p.purchase_type == ?', 'classroom_manual')
                ->andWhere('p.purchse_type_eschool_id == ?', $this->eschool_id)
                ->execute();
        return $purchases;
    }
    public function getManualPaymentEndDate()
    {
        $purchase = Doctrine::getTable('GcrPurchase')->createQuery('p')
            ->where('p.purchase_type_id = ?', $this->user_institution_id)
            ->andWhere('p.purchase_type = ?', 'classroom_manual')
            ->orderBy('p.bill_cycle DESC')
            ->fetchOne();
        if ($purchase)
        {
            return $purchase->getBillCycle();
        }
        else
        {
            return 0;
        }
    }
    public function getNextPaymentDate()
    {
        $manual_payment_ts = $this->getManualPaymentEndDate();
        $purchase = $this->getPurchase();
        if (!$purchase)
        {
            if ($manual_payment_ts == 0)
            {
                return false;
            }
            else
            {
                return $manual_payment_ts;
            }
        }
        $paypal_records = Doctrine::getTable('GcrPaypal')->findByRecurringPaymentId($purchase->profile_id);
        if (count($paypal_records) == 0)
        {
            $next_payment_ts = $purchase->trans_time;
        }
        else
        {
            $most_recent_payment = null;
            foreach($paypal_records as $record)
            {
                if (!$most_recent_payment || $record->getPaymentDate() > $most_recent_payment->getPaymentDate())
                {
                    $most_recent_payment = $record;
                }
            }
            $next_payment_ts = GcrPurchaseTable::getNextBillingDate($most_recent_payment->getPaymentDate(), $purchase->bill_cycle);
        }
        if ($manual_payment_ts < $next_payment_ts)
        {
            return $next_payment_ts;
        }
        else
        {
            return $manual_payment_ts;
        }
    }
    public function getCourseSales()
    {        
        return Doctrine::getTable('GcrPurchase')
                 ->createQuery('p')
                 ->where('p.seller_institution_id = ?', $this->user_institution_id)
                 ->andWhere('p.seller_id = ?', $this->user_id)
                 ->andWhere('p.purchase_type_eschool_id = ?', $this->eschool_id)
                 ->execute();
    }
    public function getInstitution()
    {
        return GcrInstitutionTable::getInstitution($this->user_institution_id);
    }
    public function getUser()
    {
        $institution = $this->getInstitution();
        if ($institution)
        {
            return $institution->getUserById($this->user_id);
        }
        return false;
    }
}
