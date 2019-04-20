<?php

class Model_Event_Detail extends Model_Base
{
	protected static $_table_name  = 'event_details';
	protected static $_primary_key = 'id';
	protected static $_created_at  = 'created_at';
	protected static $_updated_at  = 'updated_at';
	protected static $_deleted_at  = 'deleted_at';
	protected static $_mysql_timestamp = true;

	public function validation($is_new = false, $factory = null)	// 引数は単なる識別子、何でもいい
	{
		$validation = Validation::forge($factory);

		// 入力ルール
		$field = $validation->add('date', '日付')
			->add_rule('required')
			->add_rule('valid_date', 'Y-m-d');
		$field = $validation->add('division_result', 'イベント結果')
			->add_rule('required');

		return $validation;
	} // function validation()

	public static function get_by_division_id($division_id)
	{
		$query = DB::select('d.*', 'e.date')
			->from([self::$_table_name, 'd'])
			->join(['events', 'e'])
			->on('e.id', '=', 'd.event_id');
		if (is_array($division_id))
		{
			$query->where('d.division_id', 'in', $division_id);
		}
		else
		{
			$query->where('d.division_id', '=', $division_id);
		}
		$query->order_by('e.date', 'desc');

		return $query->as_object('Model_Event_Detail')->execute()->as_array();
	} // function get_by_division_id()
} // class Model_Event_Detail