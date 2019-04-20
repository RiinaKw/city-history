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
class Controller_View extends Controller_Layout
{
	public function action_index()
	{
		$path = $this->param('path');
		$division = Model_Division::get_by_path($path);

		$events = Model_Event_Detail::get_by_division_id($division->id);
		/*
		$end = Model_Event::find_by_pk($division->end_event_id);
		array_unshift($events, $end);
		$events[] = Model_Event::find_by_pk($division->start_event_id);
		*/
		foreach ($events as &$event)
		{
			$divisions = Model_Event::get_relative_division($event->event_id);
			if ($divisions)
			{
				foreach ($divisions as &$d)
				{
					$d_path = $d->get_path(null, true);
					$d->url_detail = Helper_Uri::create('view.division', ['path' => $d_path]);
				}
			}
			$event->divisions = $divisions;
		}

		// ビューを設定
		$content = View_Smarty::forge('city_timeline.tpl');
		$content->path = $path;
		$content->events = $events;
		$content->url_event_add = Helper_Uri::create('event.add');
		$content->url_event_edit = Helper_Uri::create('event.edit');
		$content->url_event_delete = Helper_Uri::create('event.delete');

		$this->_set_view_var('content', $content);
		$this->_set_view_var('title', $path);
		return $this->_get_view();
	} // function action_index()

	public function action_list()
	{
		$divisions = Model_Division::find_all();
		foreach ($divisions as &$division)
		{
			$division->path = $division->get_path(null, true);
		}

		// ビューを設定
		$content = View_Smarty::forge('list.tpl');
		$content->divisions = $divisions;

		$this->_set_view_var('content', $content);
		$this->_set_view_var('title', 'hello');
		return $this->_get_view();
	} // function action_list()
} // class Controller_View
