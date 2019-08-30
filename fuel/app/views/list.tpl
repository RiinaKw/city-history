
			<header class="clearfix">
				<div class="float-left">
					<h2>{{$division->path|escape}}</h2>
					<p>{{$path_kana}}</p>
				</div>
{{if $user}}
				<nav class="float-right">
					<button class="btn btn-success mb-1" data-toggle="modal" data-target="#add-division">
						<i class="fa fa-plus"></i>
						自治体追加
					</button>
				</nav>
{{/if}}
			</header>

			<nav class="list-nav">
				<ol>
					<li class="{{if ! $date}}active{{/if}}">
						<a href="{{$url_all}}">
							すべて
						</a>
					</li>
{{foreach from=$reference_dates item=cur_date}}
					<li class="{{if $date == $cur_date.date}}active{{/if}}">
						<a href="{{$cur_date.url|escape}}">
							{{$cur_date.date|date_format2:'Y(Jk)-m-d'}} - {{$cur_date.description|escape}}
						</a>
					</li>
{{/foreach}}
				</ol>
			</nav>

			<section>
				<h3>
					<a class="{{if $division.is_unfinished}}unfinished{{/if}}" href="{{$division.url_detail}}">
						{{$division->get_fullname()}}
					</a>
				</h3>
				<p class="count">{{strip}}
					{{foreach from=$count key=postfix item=cur_count}}
						{{if $cur_count}}
							{{$cur_count}}{{$postfix}}
						{{/if}}
					{{/foreach}}
				{{/strip}}</p>
				<div class="grid-container">
{{if isset($tree['支庁']) && $tree['支庁']}}
					<section class="grid departs">
						<ul class="divisions">
{{foreach from=$tree['支庁'] item=depart}}
							<li>
								<article>
									<header>
										<h4>
											<a class="{{if $depart.is_unfinished}}unfinished{{/if}}" href="{{$depart.url_detail}}">
												{{$depart->get_fullname()}}
											</a>
										</h4>
									</header>
								</article>
							</li>
{{/foreach}}
						</ul>
					</section><!-- .grid.departs -->
{{/if}}
{{if isset($tree['区']) && $tree['区']}}
					<section class="{{if $division->postfix != '市'}}grid{{/if}} wards">
						<ul class="divisions">
{{foreach from=$tree['区'] item=ward}}
							<li>
								<article>
									<header>
										<h4>
											<a class="{{if $ward.is_unfinished}}unfinished{{/if}}" href="{{$ward.url_detail}}">
												{{$ward->get_fullname()}}
											</a>
										</h4>
									</header>
								</article>
							</li>
{{/foreach}}
						</ul>
					</section><!-- .grid.wards -->
{{/if}}
{{if isset($tree['市']) && $tree['市']}}
					<section class="grid cities">
						<ul class="divisions">
{{foreach name=city from=$tree['市'] item=city}}
							<li>
								<article>
									<header>
										<h4>
											<a class="{{if $city.is_unfinished}}unfinished{{/if}}" href="{{$city.url_detail}}">
												{{$city->get_fullname()}}
											</a>
										</h4>
{{if isset($city->_children['区']) && $city->_children['区']}}
										<p class="count">{{strip}}
											{{$city->_count['区']}}区
										{{/strip}}</p>
										<ul class="divisions">
{{foreach from=$city->_children['区'] item=ward}}
											<li>
												<article>
													<header>
														<h4>
															<a class="{{if $ward.is_unfinished}}unfinished{{/if}}" href="{{$ward.url_detail}}">
																{{$ward->get_fullname()}}
															</a>
														</h4>
													</header>
												</article>
											</li>
{{/foreach}}
										</ul>
{{/if}}
									</header>
								</article>
							</li>
{{if ! $smarty.foreach.city.last && isset($city->_children['区']) && $city->_children['区']}}
						</ul>
					</section><!-- .grid.cities -->
					<section class="grid cities">
						<ul class="divisions">
{{/if}}
{{/foreach}}
						</ul>
					</section><!-- .grid.cities -->
{{/if}}
{{if isset($tree['町村']) && $tree['町村']}}
					<section class="{{if $division->postfix != '郡' && $division->postfix != '支庁'}}grid{{/if}} towns">
						<ul class="divisions">
{{foreach from=$tree['町村'] item=town}}
							<li>
								<article>
									<header>
										<h4>
											<a class="{{if $town.is_unfinished}}unfinished{{/if}}" href="{{$town.url_detail}}">
												{{$town->get_fullname()}}
											</a>
										</h4>
									</header>
								</article>
							</li>
{{/foreach}}
						</ul>
					</section><!-- .grid.towns -->
{{/if}}
{{if isset($tree['郡']) && $tree['郡']}}
{{foreach from=$tree['郡'] item=country}}
					<section class="grid countries">
						<article>
							<header>
								<h4>
									<a class="{{if $country.is_unfinished}}unfinished{{/if}}" href="{{$country.url_detail}}">
										{{$country->get_fullname()}}
									</a>
								</h4>
								<p class="count">{{strip}}
									{{foreach from=$country->_count key=postfix item=count}}
										{{if $count}}
											{{$count}}{{$postfix}}
										{{/if}}
									{{/foreach}}
								{{/strip}}</p>
								<ul class="divisions">
{{if isset($country->_children['町村']) && $country->_children['町村']}}
{{foreach from=$country->_children['町村'] item=town}}
									<li>
										<article>
											<header>
												<h5>
													<a class="{{if $town.is_unfinished}}unfinished{{/if}}" href="{{$town.url_detail}}">
														{{$town->get_fullname()}}
													</a>
												</h5>
											</header>
										</article>
									</li>
{{/foreach}}
{{/if}}
								</ul>
							</header>
						</article>
					</section><!-- .grid.countries -->
{{/foreach}}
{{/if}}
				</div><!-- .grid-container -->
			</section>

{{if $user}}

{{$components.add_division}}

{{/if}}
