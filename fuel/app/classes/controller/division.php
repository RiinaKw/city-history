<?php
/**
 * The View Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Division extends Controller_Layout
{
	const SESSION_LIST = 'division';

	public function action_detail()
	{
		$path = $this->param('path');
		$division = Model_Division::get_by_path($path);
		if ( ! $division || $division->get_path(null, true) != $path)
		{
			throw new HttpNotFoundException('自治体が見つかりません。');
		}

		$events = Model_Event_Detail::get_by_division_id($division->id);
		// 終了インベントを先頭に
		foreach ($events as $key => $event)
		{
			if ($event->event_id == $division->end_event_id)
			{
				unset($events[$key]);
				array_unshift($events, $event);
				break;
			}
		}
		// 開始イベントを末尾に
		foreach ($events as $key => $event)
		{
			if ($event->event_id == $division->start_event_id)
			{
				unset($events[$key]);
				array_push($events, $event);
				break;
			}
		}
		foreach ($events as $event)
		{
			$event->birth = false;
			$event->live = false;
			$event->death = false;
			if ($division->start_event_id == $event->event_id)
			{
				$event->birth = true;
			}
			else if ($division->end_event_id == $event->event_id)
			{
				$event->death = true;
			}
			switch ($event->division_result)
			{
				case '存続':
					$event->live = true;
				break;
				case '廃止':
				case '分割廃止':
					$event->death = true;
				break;
			}
			$divisions = Model_Event::get_relative_division($event->event_id);
			if ($divisions)
			{
				foreach ($divisions as $d)
				{
					$d_path = $d->get_path(null, true);
					$d->url_detail = Helper_Uri::create('division.detail', ['path' => $d_path]);
					if ($d->geoshape)
					{
						$d->url_geoshape = Helper_Uri::create('geoshape', ['path' => $d->geoshape]);
					}
					else
					{
						$d->url_geoshape = '';
					}
					$d->split = ($d->division_result == '分割廃止');
				}
			}
			$event->divisions = $divisions;
		} // foreach ($events as &$event)

		$breadcrumbs_arr = Helper_Breadcrumb::breadcrumb_and_kana($path);
		$breadcrumbs = $breadcrumbs_arr['breadcrumbs'];
		$path_kana = $breadcrumbs_arr['path_kana'];

		$belongs_division = Model_Division::find_by_pk($division->belongs_division_id);
		if ($belongs_division)
		{
			$belongs_division->url_detail = Helper_Uri::create('division.detail', ['path' => $belongs_division->get_path(null, true)]);
		}

		// meta description
		$description = $path.'（'.$path_kana.') ';
		foreach ($events as $event)
		{
			$event_parent = Model_Event::find_by_pk($event->event_id);
			$date = Helper_Date::date('Y(Jk)-m-d', $event_parent->date);
			$description .= ' | '.$date.' '.$event_parent->type;
		}

		Session::set(self::SESSION_LIST, Helper_Uri::current());

		// ビューを設定
		$content = View_Smarty::forge('timeline.tpl');
		$content->current = 'detail';
		$content->path = $path;
		$content->path_kana = $path_kana;
		$content->division = $division;
		$content->belongs_division = $belongs_division;
		$content->events = $events;
		$content->url_detail = Helper_Uri::create('list.division', ['path' => $path]);
		$content->url_detail_timeline = Helper_Uri::create('division.detail', ['path' => $path]);
		$content->url_children_timeline = $this->_get_children_url($path);
		$content->url_add = Helper_Uri::create('division.add');
		$content->url_edit = Helper_Uri::create('division.edit', ['path' => $path]);
		$content->url_delete = Helper_Uri::create('division.delete', ['path' => $path]);
		$content->url_event_detail = Helper_Uri::create('event.detail');
		$content->url_event_add = Helper_Uri::create('event.add');
		$content->url_event_edit = Helper_Uri::create('event.edit');
		$content->url_event_delete = Helper_Uri::create('event.delete');

		$components = [
			'add_division' => View_Smarty::forge('components/add_division.tpl'),
			'edit_division' => View_Smarty::forge('components/edit_division.tpl'),
			'delete_division' => View_Smarty::forge('components/delete_division.tpl'),
			'change_event' => View_Smarty::forge('components/change_event.tpl'),
		];
		$content->components = $components;

		$this->_set_view_var('content', $content);
		$this->_set_view_var('title', $path);
		$this->_set_view_var('description', $description);
		$this->_set_view_var('og_type', 'article');
		$this->_set_view_var('breadcrumbs', $breadcrumbs);
		return $this->_get_view();
	} // function action_detail()

	public function action_children()
	{
		$path = $this->param('path');
		$label = Input::get('label');
		$start = Input::get('start');
		$end = Input::get('end');
		$division = Model_Division::get_by_path($path);

		$division_id_arr = Model_Division::get_by_parent_division_id_and_date($division->id);

		$events_arr = [];
		if ($division_id_arr)
		{
			$events = Model_Event_Detail::get_by_division_id($division_id_arr, $start, $end);
			foreach ($events as &$event)
			{
				if (isset($events_arr[$event->event_id]))
				{
					continue;
				}
				$event->birth = false;
				$event->live = false;
				$event->death = false;
				if ($division->start_event_id == $event->event_id)
				{
					$event->birth = true;
				}
				else if ($division->end_event_id == $event->event_id)
				{
					$event->death = true;
				}
				switch ($event->division_result)
				{
					case '存続':
						$event->live = true;
					break;
					case '廃止':
						$event->death = true;
					break;
				}
				$divisions = Model_Event::get_relative_division($event->event_id);
				if ($divisions)
				{
					foreach ($divisions as &$d)
					{
						$d_path = $d->get_path(null, true);
						$d->url_detail = Helper_Uri::create('division.detail', ['path' => $d_path]);
						if ($d->geoshape)
						{
							$d->url_geoshape = Helper_Uri::create('geoshape', ['path' => $d->geoshape]);
						}
						else
						{
							$d->url_geoshape = '';
						}
						$d->split = ($d->division_result == '分割廃止');
					}
				}
				$event->divisions = $divisions;
				$events_arr[$event->event_id] = $event;
			}
		} // if ($division_id_arr)

		$breadcrumbs_arr = Helper_Breadcrumb::breadcrumb_and_kana($path);
		$breadcrumbs = $breadcrumbs_arr['breadcrumbs'];
		$path_kana = $breadcrumbs_arr['path_kana'];

		$belongs_division = Model_Division::find_by_pk($division->belongs_division_id);
		if ($belongs_division)
		{
			$belongs_division->url_detail = Helper_Uri::create('division.detail', ['path' => $belongs_division->get_path(null, true)]);
		}

		// meta description
		$description = $path.'（'.$path_kana.') ';
		if ($division_id_arr)
		{
			foreach ($events as $event)
			{
				$event_parent = Model_Event::find_by_pk($event->event_id);
				$date = Helper_Date::date('Y(Jk)-m-d', $event_parent->date);
				$description .= ' | '.$date.' '.$event_parent->type;
			}
		}

		Session::set(self::SESSION_LIST, Helper_Uri::current());

		// ビューを設定
		$content = View_Smarty::forge('timeline.tpl');
		$content->current = $label;
		$content->path = $path;
		$content->path_kana = $path_kana;
		$content->division = $division;
		$content->belongs_division = $belongs_division;
		$content->events = $events_arr;
		$content->url_detail = Helper_Uri::create('list.division', ['path' => $path]);
		$content->url_detail_timeline = Helper_Uri::create('division.detail', ['path' => $path]);
		$content->url_children_timeline = $this->_get_children_url($path);
		$content->url_add = Helper_Uri::create('division.add');
		$content->url_edit = Helper_Uri::create('division.edit', ['path' => $path]);
		$content->url_delete = Helper_Uri::create('division.delete', ['path' => $path]);
		$content->url_event_detail = Helper_Uri::create('event.detail');
		$content->url_event_add = Helper_Uri::create('event.add');
		$content->url_event_edit = Helper_Uri::create('event.edit');
		$content->url_event_delete = Helper_Uri::create('event.delete');

		$components = [
			'add_division' => View_Smarty::forge('components/add_division.tpl'),
			'edit_division' => View_Smarty::forge('components/edit_division.tpl'),
			'delete_division' => View_Smarty::forge('components/delete_division.tpl'),
			'change_event' => View_Smarty::forge('components/change_event.tpl'),
		];
		$content->components = $components;

		$this->_set_view_var('content', $content);
		$this->_set_view_var('title', $path);
		$this->_set_view_var('description', $description);
		$this->_set_view_var('og_type', 'article');
		$this->_set_view_var('breadcrumbs', $breadcrumbs);
		return $this->_get_view();
	} // function action_children()

	protected function _get_children_url($path)
	{
		return [
			'平成' => Helper_Uri::create('division.children', ['label' => '平成', 'path' => $path, 'start' => '1989-01-01', 'end' => '2019-04-01']),
			'昭和後期' => Helper_Uri::create('division.children', ['label' => '昭和後期', 'path' => $path, 'start' => '1950-01-01', 'end' => '1988-12-31']),
			'大正～昭和前期' => Helper_Uri::create('division.children', ['label' => '大正～昭和前期', 'path' => $path, 'start' => '1912-01-01', 'end' => '1949-12-31']),
			'明治' => Helper_Uri::create('division.children', ['label' => '明治', 'path' => $path, 'start' => '1878-01-01', 'end' => '1911-12-31']),
		];
	}

	public function action_add()
	{
		if ( ! $this->user)
		{
			throw new HttpNoAccessException("permission denied");
		}
		if ( ! Input::post())
		{
			throw new HttpBadRequestException("post required");
		}

		$division = Model_Division::forge();
		$division->create(Input::post());

		Model_Activity::insert_log([
			'user_id' => Session::get('user.id'),
			'target' => 'add division',
			'target_id' => $division->id,
		]);

		$path_new = $division->get_path(null, true);

		Helper_Uri::redirect('division.detail', ['path' => $path_new]);
		return;
	} // function action_add()

	public function action_edit()
	{
		if ( ! $this->user)
		{
			throw new HttpNoAccessException("permission denied");
		}

		$path = $this->param('path');
		$division = Model_Division::get_by_path($path);
		$division->create(Input::post());

		Model_Activity::insert_log([
			'user_id' => Session::get('user.id'),
			'target' => 'edit division',
			'target_id' => $division->id,
		]);

		$path_new = $division->get_path(null, true);

		Helper_Uri::redirect('division.detail', ['path' => $path_new]);
		return;
	} // function action_edit()

	public function action_delete()
	{
		if ( ! $this->user)
		{
			throw new HttpNoAccessException("permission denied");
		}

		$path = $this->param('path');
		$division = Model_Division::get_by_path($path);
		$path = $division->get_parent_path();
		$division->soft_delete();

		Model_Activity::insert_log([
			'user_id' => Session::get('user.id'),
			'target' => 'delete division',
			'target_id' => $division->id,
		]);

		if ($path)
		{
			Helper_Uri::redirect('division.detail', ['path' => $path]);
		}
		else
		{
			Helper_Uri::redirect('top');
		}
	} // function action_delete()
} // class Controller_Division
