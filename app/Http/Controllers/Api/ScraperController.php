<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ScraperController as WebScraperController;
use Illuminate\Http\Request;

class ScraperController extends ApiController
{
    public function scrape(Request $request, WebScraperController $ctrl)
    {
        return $ctrl->scrape($request);
    }

    public function discover(Request $request, WebScraperController $ctrl)
    {
        return $ctrl->discover($request);
    }

    public function import(Request $request, WebScraperController $ctrl)
    {
        return $ctrl->import($request);
    }
}
