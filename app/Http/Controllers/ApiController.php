<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cache;
use \Zttp\Zttp;
use App\Instance;

class ApiController extends Controller
{
	public function instances(Request $request)
	{
		$this->validate($request, [
			'page' => 'nullable|integer|min:1',
			'latestVersionOnly' => 'nullable',
			'allowsVideos' => 'nullable',
			'openRegistration' => 'nullable',
		]);

		$i = Instance::query();

		$i->whereNotNull('approved_at');

		if($request->openRegistration == 'true') {
			$i->where('nodeinfo->openRegistrations', true);
		}

		if($request->latestVersionOnly == 'true') {
			$i->where('nodeinfo->software->version', '0.10.6');
		}

		if($request->allowsVideos == 'true') {
			$i->whereJsonContains('nodeinfo->metadata->config->uploader', 'video/mp4');
		}
		
		return $i->inRandomOrder()->paginate(10);
	}

	public function instance(Request $request, $domain)
	{
		$instance = Instance::whereNotNull('approved_at')
			->whereDomain($domain)
			->firstOrFail();
		return $instance;
	}

	public function instanceTimeline(Request $request, $domain)
	{
		$instance = Instance::whereNotNull('approved_at')
			->whereDomain($domain)
			->firstOrFail();

		$res = Cache::remember('instance:timeline:'.$instance->id, now()->addHours(12), function() use($domain){
			$url = "https://{$domain}/api/v1/timelines/public";
			$timeline = Zttp::get($url, [
				'limit' => 20
			]);
			return $timeline->json();
		});

		return $res;
	}
}
