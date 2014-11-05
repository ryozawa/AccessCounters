<?php
/**
 * AccessCounter Model
 *
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Ryo Ozawa <ozawa.ryo@withone.co.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AccessCountersAppModel', 'AccessCounters.Model');

/**
 * AccessCounter Model
 *
 * @author Ryo Ozawa <ozawa.ryo@withone.co.jp>
 * @package NetCommons\AccessCounters\Model
 */
class AccessCounter extends AccessCountersAppModel {

	const DISPLAY_TYPE_LABEL_0 = 'default';

	const DISPLAY_TYPE_LABEL_1 = 'primary';

	const DISPLAY_TYPE_LABEL_2 = 'success';

	const DISPLAY_TYPE_LABEL_3 = 'info';

	const DISPLAY_TYPE_LABEL_4 = 'warning';

	const DISPLAY_TYPE_LABEL_5 = 'danger';

	const DISPLAY_DIGIT_MIN = 3;

	const DISPLAY_DIGIT_MAX = 9;

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'block_key' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => true,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'count' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => true,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
			'naturalNumber' => array(
				'rule' => array('naturalNumber', true),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
			'range' => array(
				'rule' => array('range', -1, 2147483648),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'count_start' => array(
			'naturalNumber' => array(
				'rule' => array('naturalNumber', true),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
			'range' => array(
				'rule' => array('range', -1, 1000000000),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);

/**
 * before save
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if the operation should continue, false if it should abort
 */
	public function beforeSave($options = array()) {
		if (! isset($this->data[$this->name]['id']) || empty($this->data[$this->name]['id'])) {
			$this->data[$this->name]['created_user'] = CakeSession::read('Auth.User.id');
		}
		$this->data[$this->name]['modified_user'] = CakeSession::read('Auth.User.id');
		return true;
	}

/**
 * get access_counter information
 *
 * @param int $blockKey blocks.key
 * @return array
 */
	public function getCounterInfo($blockKey) {
		$fields = array(
			'AccessCounter.id',
			'AccessCounter.block_key',
			'AccessCounter.count',
			'AccessCounter.count_start',
			'Block.id',
			'Frame.id',
			'AccessCounterFrameSetting.id',
			'AccessCounterFrameSetting.frame_key',
			'AccessCounterFrameSetting.display_type',
			'AccessCounterFrameSetting.display_digit',
		);
		$conditions = array('AccessCounter.block_key' => $blockKey);
		$joins = array(
			array(
				'type' => 'INNER',
				'table' => 'blocks',
				'alias' => 'Block',
				'conditions' => 'AccessCounter.block_key = Block.key',
			),
			array(
				'type' => 'INNER',
				'table' => 'frames',
				'alias' => 'Frame',
				'conditions' => 'Block.id = Frame.block_id',
			),
			array(
				'type' => 'INNER',
				'table' => 'access_counter_frame_settings',
				'alias' => 'AccessCounterFrameSetting',
				'conditions' => 'Frame.key = AccessCounterFrameSetting.frame_key',
			),
		);
		$order = 'AccessCounter.id DESC';

		// カウンタ情報取得
		$counter = $this->find('first', array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins,
				'order' => $order
			));

		if (!empty($counter)) {
			// カウント開始フラグの付与
			$counter['AccessCounter']['is_started'] = true; // カウント開始済

			// 表示タイプのラベル名取得
			$displayType = $counter['AccessCounterFrameSetting']['display_type'];
			$counter['AccessCounterFrameSetting']['display_type_label'] = $this->getDisplayTypeLabel($displayType);
		} else {
			// カウント開始前の初期情報
			$counter = array(
				'AccessCounter' => array(
					'count_start' => 0,
					'is_started' => false, // カウント開始前
				),
				'AccessCounterFrameSetting' => array(
					'id' => null,
					'display_type' => '0',
					'display_digit' => AccessCounter::DISPLAY_DIGIT_MIN,
					'display_type_label' => AccessCounter::DISPLAY_TYPE_LABEL_0,
				)
			);
		}

		return $counter;
	}

/**
 * get display_type label
 *
 * @param int $displayType AccessCounterFrameSetting.display_type
 * @return String
 */
	public static function getDisplayTypeLabel($displayType) {
		try {
			return constant("self::DISPLAY_TYPE_LABEL_" . $displayType);
		} catch (Exception $e) {
			return self::DISPLAY_TYPE_LABEL_0;
		}
	}

/**
 * get display_type options
 *
 * @return array
 */
	public static function getDisplayTypeOptions() {
		return array(
			self::DISPLAY_TYPE_LABEL_0,
			self::DISPLAY_TYPE_LABEL_1,
			self::DISPLAY_TYPE_LABEL_2,
			self::DISPLAY_TYPE_LABEL_3,
			self::DISPLAY_TYPE_LABEL_4,
			self::DISPLAY_TYPE_LABEL_5,
		);
	}

/**
 * get display_digit options
 *
 * @return array
 */
	public static function getDisplayDigitOptions() {
		$displayDigitOptions = array();
		for ($digit = self::DISPLAY_DIGIT_MIN; $digit <= self::DISPLAY_DIGIT_MAX; $digit++) {
			$displayDigitOptions[$digit] = $digit;
		}
		return $displayDigitOptions;
	}
}
