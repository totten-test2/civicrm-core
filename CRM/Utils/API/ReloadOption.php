<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * Implement the "api.reload" option. This option can be used with "create" to
 * force the API to reload a clean copy of the entity before returning the result.
 *
 * @code
 * $clean = civicrm_api('myentity', 'create', array(
 *   'options' => array(
 *     'reload' => 1
 *   ),
 * ));
 * @endcode
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 */

require_once 'api/Wrapper.php';
class CRM_Utils_API_ReloadOption implements API_Wrapper {

  /**
   * @var null|'null'|'default'|'selected'
   */
  private $reloadMode = NULL;

  /**
   * {@inheritDoc}
   */
  public function fromApiInput($apiRequest) {
    if ($apiRequest['action'] === 'create' && isset($apiRequest['params'], $apiRequest['params']['options'], $apiRequest['params']['options']['reload'])) {
      $this->reloadMode = $apiRequest['params']['options']['reload'];
    }
    return $apiRequest;
  }

  /**
   * {@inheritDoc}
   */
  public function toApiOutput($apiRequest, $result) {
    if ($result['is_error']) {
      return $result;
    }
    switch ($this->reloadMode) {
      case NULL:
      case '0':
      case 'null':
        return $result;

      case '1':
      case 'default':
        $params = array(
          'id' => $result['id'],
        );
        $reloadResult = civicrm_api3($apiRequest['entity'], 'get', $params);
        $result['values'][$result['id']] = array_merge($result['values'][$result['id']], $reloadResult['values'][$result['id']]);
        return $result;

      case 'selected':
        $params = array(
          'id' => $result['id'],
          'return' => $this->pickReturnFields($apiRequest),
        );
        $reloadResult = civicrm_api3($apiRequest['entity'], 'get', $params);
        $result['values'][$result['id']] = array_merge($result['values'][$result['id']], $reloadResult['values'][$result['id']]);
        return $result;

      default:
        throw new API_Exception("Unknown reload mode: " . var_export($this->reloadMode, TRUE));
    }
  }

  /**
   * Identify the fields which should be returned
   *
   * @param $apiRequest
   * @return array
   */
  public function pickReturnFields($apiRequest) {
    $fields = civicrm_api3($apiRequest['entity'], 'getfields', array());
    $returnKeys = array_intersect(
      array_keys($apiRequest['params']),
      array_keys($fields['values'])
    );
    return $returnKeys;
  }
}
