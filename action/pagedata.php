<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Gina Haeussge <gina@foosel.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_blogtng_pagedata extends DokuWiki_Action_Plugin{

    var $entry;

    function action_plugin_blogtng_pagedata() {
        $this->entryhelper =& plugin_load('helper', 'blogtng_entry');
    }

    function getInfo() {
        return confToHash(dirname(__FILE__).'/../INFO');
    }

    function register(&$controller) {
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this, 'update_data', array());
    }

    /**
     * Updates the metadata in the blogtng database.
     */
    function update_data(&$event, $params) {
        global $ID;
        global $auth;

        $data = $event->data;

        // if date_created is not set, we are still in the first run of the
        // metadata rendering processed as triggered by p_set_metadata, so we
        // refuse to do any work here
        //
        // if that stupid behavior of double-rendering the metadata with missing
        // data in the first run is to be fixed in the future, the two lines
        // below can be happily removed again ;)
        if (!$data['persistent']['date']['created'])
            return;

        // fetch author info
        $creator = $data['current']['creator'];
        $userdata = false;
        if ($auth != null)
            $userdata = $auth->getUserData($creator);

        // fetch dates
        $date_created = $data['persistent']['date']['created'];
        $date_modified = $data['current']['date']['modified'];

        // prepare entry ...
        $pid = md5($ID);
        $this->entryhelper->load_by_pid($pid);
        $this->entryhelper->entry['page'] = $ID;
        $this->entryhelper->entry['title'] = $data['current']['title'];
        $this->entryhelper->entry['image'] = $data['current']['relation']['firstimage'];
        $this->entryhelper->entry['created'] = $date_created;
        $this->entryhelper->entry['lastmod'] = (!$date_modified) ? $date_created : $date_modified;
        $this->entryhelper->entry['login'] = $creator;
        $this->entryhelper->entry['author'] = ($userdata) ? $userdata['name'] : $creator;
        $this->entryhelper->entry['email'] = ($userdata) ? $userdata['email'] : '';

        // ... and save it
        $this->entryhelper->save();
    }

}
// vim:ts=4:sw=4:et:enc=utf-8: