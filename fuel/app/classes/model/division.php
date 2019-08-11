<?php

class Model_Division extends Model_Base
{
	protected static $_table_name  = 'divisions';
	protected static $_primary_key = 'id';
	protected static $_created_at  = 'created_at';
	protected static $_updated_at  = 'updated_at';
	protected static $_deleted_at  = 'deleted_at';
	protected static $_mysql_timestamp = true;

	public function validation($is_new = false, $factory = null)	// 引数は単なる識別子、何でもいい
	{
		$validation = Validation::forge($factory);

		// 入力ルール
		$field = $validation->add('name', '自治体名')
			->add_rule('required')
			->add_rule('max_length', 20);
		$field = $validation->add('name_kana', '自治体名かな')
			->add_rule('required')
			->add_rule('max_length', 20);
		$field = $validation->add('postfix', '自治体名種別')
			->add_rule('required')
			->add_rule('max_length', 20);
		$field = $validation->add('postfix_kana', '自治体名種別かな')
			->add_rule('required')
			->add_rule('max_length', 20);
		$field = $validation->add('identify', '識別名')
			->add_rule('max_length', 50);
		$field = $validation->add('parent_division_id', '親自治体');
		$field = $validation->add('start_event_id',     '設置イベント');
		$field = $validation->add('end_event_id',       '廃止イベント');
		$field = $validation->add('government_code',    '全国地方公共団体コード')
			->add_rule('min_length', 6)
			->add_rule('max_length', 7);
		$field = $validation->add('display_order', '表示順')
			->add_rule('valid_string', array('numeric'));

		return $validation;
	} // function validation()

	public static function get_all()
	{
		$query = DB::select()
			->from(self::$_table_name)
			->where('deleted_at', '=', null)
			->order_by('name_kana', 'ASC');

		return $query->as_object('Model_Division')->execute()->as_array();
	} // function get_all()

	public static function get_all_id()
	{
		$query = DB::select('id')
			->from(self::$_table_name)
			->where('deleted_at', '=', null)
			->order_by('name_kana', 'ASC');

		$result = $query->execute()->as_array();
		$arr = [];
		foreach ($result as $item)
		{
			$arr[] = $item['id'];
		}
		return $arr;
	} // function get_all_id()

	public static function query($q)
	{
		$query = DB::select()
			->from(self::$_table_name)
			->where('deleted_at', '=', null)
			//->where(DB::expr('MATCH(fullname)'), 'AGAINST', DB::expr('(\'+'.$q.'\' IN BOOLEAN MODE)'));
			->where('fullname', 'LIKE', '%'.$q.'%');

		return $query->as_object('Model_Division')->execute()->as_array();
	}

	public static function search($q)
	{
		$q = str_replace(array('\\', '%', '_'), array('\\\\', '\%', '\_'), $q);
		$q_arr = preg_split('/(\s+)|(　+)/', $q);
		$query = DB::select()
			->from(self::$_table_name)
			->where('deleted_at', '=', null)
			->order_by('name_kana', 'ASC');
		foreach ($q_arr as $word)
		{
			$query->and_where_open()
				->where('fullname', 'LIKE', '%'.$word.'%')
				->or_where('fullname_kana', 'LIKE', '%'.$word.'%')
				->and_where_close();
		}

		return $query->as_object('Model_Division')->execute()->as_array();
	} // function search()

	public static function set_path($path)
	{
		$arr = explode('/', $path);
		$parent_id = null;
		$divisions = [];
		foreach ($arr as $name)
		{
			preg_match('/^(?<place>.+?)(?<postfix>都|府|県|支庁|市|郡|区|町|村|郷|城下|駅|宿|組|新田)(\((?<identify>.+?)\))?$/', $name, $matches);
			if ( ! $division = self::get_one_by_name_and_parent_id($matches, $parent_id))
			{
				$division = self::forge([
					'name' => $matches['place'],
					'name_kana' => '',
					'postfix' => $matches['postfix'],
					'postfix_kana' => '',
					'fullname' => '',
					'fullname_kana' => '',
					'show_postfix' => true,
					'identify' => (isset($matches['identify']) ? $matches['identify'] : null),
					'parent_division_id' => $parent_id,
					'is_unfinished' => true,
				]);
				$division->fullname = $division->get_path(null, true);
				$division->save();

				Model_Activity::insert_log([
					'user_id' => Session::get('user_id'),
					'target' => 'add division',
					'target_id' => $division->id,
				]);
			}
			$divisions[] = $division;
			$parent_id = $division->id;
		}
		return $divisions;
	} // function set_path()

	public static function get_by_path($path, $parent_id = null)
	{
		$arr = explode('/', $path);
		$division = null;
		$parent_id = null;
		foreach ($arr as $name)
		{
			preg_match('/^(?<place>.+?)(?<postfix>都|府|県|支庁|市|郡|区|町|村|郷|城下|駅|宿|組|新田)(\((?<identify>.+?)\))?$/', $name, $matches);

			if ($matches)
			{
				$result = self::get_one_by_name_and_parent_id($matches, $parent_id);
				if ($result)
				{
					$division = $result;
					$parent_id = $division->id;
				}
				else
				{
					$division = null;
					$parent_id = null;
					break;
				}
			}
			else
			{
				$matches = array(
					'place' => $name,
					'postfix' => '',
				);
				$result = self::get_one_by_name_and_parent_id($matches, $parent_id);
				if ($result)
				{
					$division = $result;
					$parent_id = $division->id;
				}
				else
				{
					$division = null;
					$parent_id = null;
					break;
				}
			}
		}
		return $division;
	} // function get_by_path()

	public static function get_one_by_name_and_parent_id($name, $parent_id)
	{
		$query = DB::select()
			->from(self::$_table_name)
			->where('parent_division_id', '=', $parent_id)
			->and_where_open()
			->and_where_open()
			->where('name', '=', $name['place'])
			->where('postfix', '=', $name['postfix'])
			->where('show_postfix', '=', true)
			->and_where_close()
			->or_where_open()
			->where('name', '=', $name['place'].$name['postfix'])
			->where('show_postfix', '=', false)
			->or_where_close()
			->and_where_close();
		if (isset($name['identify']))
		{
			$query->where('identify', '=', $name['identify']);
		}

		$result = $query->as_object('Model_Division')->execute()->as_array();
		if ($result)
		{
			return $result[0];
		}
		else {
			return null;
		}
	} // function _get_one_by_name_and_parent_id()

	public static function get_top_level()
	{
		$query = DB::select()
			->from(self::$_table_name)
			->where('deleted_at', '=', null)
			->where('parent_division_id', '=', null)
			->order_by('display_order', 'asc');

		return $query->as_object('Model_Division')->execute()->as_array();
	} // function get_top_level()

	public static function get_by_postfix_and_date($parent_id, $postfix, $date = null)
	{
		$query = DB::select('d.*')
			->from([self::$_table_name, 'd'])
			->join(['events', 's'], 'LEFT OUTER')
			->on('d.start_event_id', '=', 's.id')
			->join(['events', 'e'], 'LEFT OUTER')
			->on('d.end_event_id', '=', 'e.id')
			->where('d.deleted_at', '=', null)
			->where('d.postfix', '=', $postfix)
			->where('d.parent_division_id', '=', $parent_id);
		if ($date)
		{
			$query->and_where_open()
				->where('s.date', '<=', $date)
				->or_where('s.date', '=', null)
				->and_where_close()
				->and_where_open()
				->where('e.date', '>=', $date)
				->or_where('e.date', '=', null)
				->and_where_close();
		}
		$query
			->order_by(DB::expr('CASE WHEN d.government_code IS NULL THEN 2 WHEN d.government_code = "" THEN 1 ELSE 0 END'), 'asc')
			->order_by('d.government_code', 'asc')
			->order_by(DB::expr('CASE WHEN d.name_kana IS NULL THEN 2 WHEN d.name_kana = "" THEN 1 ELSE 0 END'), 'asc')
			->order_by('d.name_kana', 'asc')
			->order_by(DB::expr('CASE WHEN e.date IS NULL THEN "9999-12-31" ELSE e.date END'), 'desc');

		return $query->as_object('Model_Division')->execute()->as_array();
	} // function get_by_postfix_and_date()

	public function get_postfix_count($date)
	{
		$query = DB::select('d.postfix', [DB::expr('COUNT(d.postfix)'), 'postfix_count'])
			->from([self::$_table_name, 'd'])
			->join(['events', 's'], 'LEFT OUTER')
			->on('d.start_event_id', '=', 's.id')
			->join(['events', 'e'], 'LEFT OUTER')
			->on('d.end_event_id', '=', 'e.id')
			->where('d.deleted_at', '=', null)
			->and_where_open()
			->where('d.parent_division_id', '=', $this->id)
			->or_where('d.belongs_division_id', '=', $this->id)
			->and_where_close()
			->group_by('d.postfix');
		if ($date)
		{
			$query->and_where_open()
				->where('s.date', '<=', $date)
				->or_where('s.date', '=', null)
				->and_where_close()
				->and_where_open()
				->where('e.date', '>=', $date)
				->or_where('e.date', '=', null)
				->and_where_close();
		}

		$arr = $query->execute()->as_array();
		$result = [];
		foreach ($arr as $item)
		{
			$result[$item['postfix']] = (int)$item['postfix_count'];
		}

		$sorted = [
			'支庁' => 0,
			'区' => 0,
			'市' => 0,
			'郡' => 0,
			'町' => 0,
			'村' => 0,
		];
		if (isset($result['支庁']) && $result['支庁'])
		{
			$sorted['支庁'] = $result['支庁'];
			unset($result['支庁']);
		}
		if (isset($result['区']) && $result['区'])
		{
			$sorted['区'] = $result['区'];
			unset($result['区']);
		}
		if (isset($result['市']) && $result['市'])
		{
			$sorted['市'] = $result['市'];
			unset($result['市']);
		}
		if (isset($result['郡']) && $result['郡'])
		{
			$sorted['郡'] = $result['郡'];
			unset($result['郡']);
		}
		if (isset($result['町']) && $result['町'])
		{
			$sorted['町'] = $result['町'];
			unset($result['町']);
		}
		if (isset($result['村']) && $result['村'])
		{
			$sorted['村'] = $result['村'];
			unset($result['村']);
		}
		return array_merge($sorted, $result);
	}

	public static function get_by_date($date = null, $parent_id = null)
	{
		$query = DB::select('d.*')
			->from([self::$_table_name, 'd'])
			->join(['events', 's'], 'LEFT OUTER')
			->on('d.start_event_id', '=', 's.id')
			->join(['events', 'e'], 'LEFT OUTER')
			->on('d.end_event_id', '=', 'e.id');
		if ($date)
		{
			$query->and_where_open()
				->where('s.date', '<=', $date)
				->or_where('s.date', '=', null)
				->and_where_close()
				->and_where_open()
				->where('e.date', '>=', $date)
				->or_where('e.date', '=', null)
				->and_where_close();
		}
		if ($parent_id)
		{
			$query->where('d.parent_division_id', '=', $parent_id);
		}
		$query->order_by('d.name_kana', 'ASC');

		$divisions = $query->as_object('Model_Division')->execute()->as_array();
		return $divisions;
	} // function get_by_date()

	public static function get_by_parent_division_id_and_date($division_id, $date = null)
	{
		$query = DB::select('d.*')
			->from([self::$_table_name, 'd'])
			->join(['events', 's'], 'LEFT OUTER')
			->on('d.start_event_id', '=', 's.id')
			->join(['events', 'e'], 'LEFT OUTER')
			->on('d.end_event_id', '=', 'e.id')
			->and_where_open()
			->where('d.parent_division_id', '=', $division_id)
			->or_where('d.belongs_division_id', '=', $division_id)
			->and_where_close()
			->where('d.deleted_at', '=', null);
		if ($date)
		{
			$query->and_where_open()
				->where('s.date', '<=', $date)
				->or_where('s.date', '=', null)
				->and_where_close()
				->and_where_open()
				->where('e.date', '>=', $date)
				->or_where('e.date', '=', null)
				->and_where_close();
		}
		$query
			->order_by(DB::expr('CASE WHEN d.government_code IS NULL THEN 2 WHEN d.government_code = "" THEN 1 ELSE 0 END'), 'asc')
			->order_by('d.government_code', 'asc')
			->order_by(DB::expr('CASE WHEN d.name_kana IS NULL THEN 2 WHEN d.name_kana = "" THEN 1 ELSE 0 END'), 'asc')
			->order_by('d.name_kana', 'asc')
			->order_by(DB::expr('CASE WHEN e.date IS NULL THEN "9999-12-31" ELSE e.date END'), 'desc');

		$divisions = $query->as_object('Model_Division')->execute()->as_array();
		$d_arr = [];
		if ($divisions)
		{
			foreach ($divisions as $d)
			{
				$d_arr[] = $d->id;
				$child_arr = static::get_by_parent_division_id_and_date($d->id, $date);
				if ($child_arr)
				{
					$d_arr = array_merge($d_arr, $child_arr);
				}
			}
		}
		return array_unique($d_arr);
	} // get_by_parent_division_id_and_date

	public function get_parent()
	{
		return self::find_by_pk($this->parent_division_id);
	} // function get_parent()

	public function get_path($current, $force_fullpath = false)
	{
		if ($force_fullpath)
		{
			$division = $this;
			$path = '';
			do {
				$name = $division->name;
				if ($division->show_postfix)
				{
					$name .= $division->postfix;
				}
				if ($division->identify)
				{
					$name .= '('.$division->identify.')';
				}
				$path = ($path ? $name.'/'.$path : $name);
				$parent_id = $division->parent_division_id;
				$division = Model_Division::find_by_pk($parent_id);
			} while ($division);

			return $path;
		}
	} // function get_path()

	public function get_kana()
	{
		$kana = $this->name_kana;
		if ($this->show_postfix)
		{
			$kana .= '・'.$this->postfix_kana;
		}
		return $kana;
	} // function get_kana()

	public function get_parent_path()
	{
		if ($this->parent_division_id)
		{
			$path = $this->get_path(null, true);
			return dirname($path);
		}
		else
		{
			return null;
		}
	} // function get_parent_path()

	public function get_belongs_path()
	{
		if ($this->belongs_division_id)
		{
			$division = self::find_by_pk($this->belongs_division_id);
			return $division->get_path(null, true);
		}
		else
		{
			return null;
		}
	} // function get_belongs_path()

	public function get_fullname()
	{
		$name = $this->name;
		if ($this->show_postfix)
		{
			$name .= $this->postfix;
		}
		if ($this->identify)
		{
			$name .= '('.$this->identify.')';
		}
		return $name;
	} // function get_fullname()

	public function create($input)
	{
		$parent = $input['parent'];
		$belongs = $input['belongs'];

		try
		{
			DB::start_transaction();

			if ($parent)
			{
				$parent_division = self::get_by_path($parent);
				if ( ! $parent_division)
				{
					$parent_division = self::set_path($parent);
					$parent_division = array_pop($parent_division);
				}
				$this->parent_division_id = $parent_division->id;
			}
			else
			{
				$this->parent_division_id = null;
			}
			if ($belongs)
			{
				$belongs_division = self::get_by_path($belongs);
				if ( ! $belongs_division)
				{
					$belongs_division = self::set_path($belongs);
					$belongs_division = array_pop($belongs_division);
				}
				$this->belongs_division_id = $belongs_division->id;
			}
			else
			{
				$this->belongs_division_id = null;
			}

			$this->name            = $input['name'];
			$this->name_kana       = $input['name_kana'];
			$this->postfix         = $input['postfix'];
			$this->postfix_kana    = $input['postfix_kana'];
			$this->show_postfix    = isset($input['show_postfix']) && $input['show_postfix'] ? true : false;
			$this->identify        = $input['identify'] ?: null;
			$this->government_code = $input['government_code'] ?: null;
			$this->display_order   = $input['display_order'] ?: null;
			$this->fullname        = '';
			$this->fullname_kana   = '';
			$this->is_unfinished   = isset($input['is_unfinished']) && $input['is_unfinished'] ? true : false;
			$this->save();

			$this->fullname = $this->get_path(null, true);
			$this->fullname_kana = $this->name_kana.$this->postfix_kana;

			$query = DB::select()
				->from(self::$_table_name)
				->where('deleted_at', '=', null)
				->where('fullname', '=', $this->fullname)
				->where('id', '!=', $this->id)
				;
			if ($query->execute()->as_array())
			{
				throw new HttpBadRequestException('重複しています。');
			}
			$this->save();

			DB::commit_transaction();
		}
		catch (HttpBadRequestException $e)
		{
			// 内部エラー
			DB::rollback_transaction();
			throw new HttpBadRequestException($e->getMessage());
		}
		catch (Exception $e)
		{
			Debug::dump($e, $e->getTraceAsString());exit;
			// 内部エラー
			DB::rollback_transaction();
			throw new HttpServerErrorException($e->getMessage());
		}
	} // function create()
} // class Model_Division
