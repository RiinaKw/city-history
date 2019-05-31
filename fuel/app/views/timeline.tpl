
			<header class="clearfix">
				<div class="float-left">
					<h2>
						{{$path|escape}}
{{if $belongs_division}}
						{{strip}}
							（
							<a href="{{$belongs_division->url_detail}}">
								{{$belongs_division->fullname|escape}}
							</a>
							）
						{{/strip}}
{{/if}}
					</h2>
					<p>{{$path_kana}}</p>
				</div>
{{if $user}}
				<nav class="float-right">
					<button class="btn btn-success mb-1" data-toggle="modal" data-target="#add-division">
						<i class="fa fa-plus"></i>
						自治体追加
					</button>
					<button class="btn btn-primary mb-1" data-toggle="modal" data-target="#edit-division">
						<i class="fa fa-edit"></i>
						自治体変更
					</button>
					<button class="btn btn-danger mb-1" data-toggle="modal" data-target="#delete-division">
						<i class="fa fa-trash"></i>
						自治体削除
					</button>
				</nav>
{{/if}}
			</header>
			<nav class="timeline-nav">
				<ul>
					<li class="">
						<a href="{{$url_detail}}">所属自治体</a>
					</li>
					<li class="{{if $current == 'detail'}}active{{/if}}">
						<a href="{{$url_detail_timeline}}">自治体タイムライン</a>
					</li>
					<li>
						所属自治体タイムライン
						<ul>
{{foreach from=$url_belongto_timeline key=label item=url}}
							<li class="{{if $current == $label}}active{{/if}}">
								<a href="{{$url}}">{{$label}}</a>
							</li>
{{/foreach}}
						</ul>
					</li>
				</ul>
		</nav>

			<div class="col-md-10 offset-md-1 pb-3">
				<section class="timeline">
{{foreach name=events from=$events item=event}}
					<article
						class="row editable {{if $event->birth}}birth{{/if}} {{if $event->live}}live{{/if}} {{if $event->death}}death{{/if}}"
						data-event-id="{{$event.event_id}}">
						<section class="col-sm-7">
							<header class="clearfix">
								<h3 class="float-left">{{$event.type|escape}}</h3>
								<time class="float-right" datetime="{{$event.date}}">{{$event.date|date_format2:'Y(Jk)-m-d'}}</time>
							</header>
							<ul>
{{foreach from=$event.divisions item=d}}
{{if ! $d->is_refer}}
								<li>
									<a href="{{$d->url_detail|escape}}" data-toggle="tooltip" title="{{$d->get_path(null, true)|escape}}">
{{if $division.id == $d.id}}
										<b>{{$d.fullname|escape}}</b>,
{{else}}
										{{$d.fullname|escape}},
{{/if}}
										{{$d.division_result|escape}}
									</a>
								</li>
{{/if}}
{{/foreach}}
							</ul>
						</section>
						<div class="map col-sm-5 mb-4" id="map-{{$event.event_id}}">
							<div class="loading">
								{{Asset::img('loading.gif')}}
							</div>
						</div>
						<script>
							$(function(){
								var shapes = [];
{{foreach from=$event.divisions item=d}}
{{if $d && $d.url_geoshape}}
								shapes.push({
									url: "{{$d.url_geoshape}}",
									split: "{{if isset($d.split)}}{{$d.split}}{{/if}}"
								});
{{/if}}
{{/foreach}}
								if (shapes.length) {
									var id = "map-{{$event.event_id}}"
									$("#" + id).show();
									create_map(id, shapes);
								}
							});
						</script>
					</article>
{{foreachelse}}
					<p>no events</p>
{{/foreach}}
{{if $user}}
					<span class="add"><i class="fas fa-plus"></i> イベントを追加…</span>
{{/if}}
				</section>
			</div>

			<script>
$(function () {
	$('[data-toggle="tooltip"]').tooltip();
});
			</script>

{{if $user}}

{{$components.add_division}}
{{$components.edit_division}}
{{$components.delete_division}}
{{$components.change_event}}

{{/if}}
