<?php

/**
 * This header is for logged in users on the course/view page. It highlights
 * the courses tab and doesn't show admin tabs for admins
 *
 * @author ron
 */
class GcrUserSymfonyCourseViewHeaderType extends GcrUserSymfonyHeaderType
{
    public function getNavDivs()
    {
        global $CFG;
        $content = '';
        if ($this->institution)
        {
            $content .= '<div id="main-nav">';
            $content .= '<ul>';
            $content .= '
            <li><a href="'. $this->institution->getAppUrl() .'" accesskey="h">Dashboard</a></li>
            <li><a href="'. $this->institution->getAppUrl() .'view" accesskey="v">Pages</a></li>
            <li><a href="'. $this->institution->getAppUrl() .'group/mygroups.php">Groups</a></li>
			<li><a href="'. $this->current_app->getUrl() .'/course/subscriptions">Subscriptions</a></li>
			<li><a href="'. $this->current_app->getUrl() .'/course/view">Courses</a></li>
			<li><a href="'. $this->current_app->getUrl() .'/course/certifications">Certifications</a></li>
			';
            if ($CFG->current_app->hasPrivilege('EschoolStaff'))
            {
                $content .= '<li><a href="'. $this->institution->getAppUrl() .'admin/users/search.php">Administration</a></li>';
            }
            $content .= '</ul></div><div id="sub-nav"></div>';
        }
        return $content;
    }
}

?>
