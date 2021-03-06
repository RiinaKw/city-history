<?php

namespace Fuel\Migrations;

class Update_id_path_in_divisions
{
	public function up()
	{
		$divisions = \Model_Division::find_all();
		if ($divisions)
		{
			foreach ($divisions as $division)
			{
				if ($division->parent_division_id === null) {
					$division->id_path = $division->id . '/';
					$division->save();
				} else {
					$parent = \Model_Division::find_by_pk($division->parent_division_id);
					$division->id_path = $parent->id_path . $division->id . '/';
					$division->save();
				}
			}
		}
	}

	public function down()
	{
	}
}
