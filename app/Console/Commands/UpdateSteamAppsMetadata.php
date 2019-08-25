<?php

namespace Zeropingheroes\Lanager\Console\Commands;

use Illuminate\Console\Command;
use Syntax\SteamApi\Exceptions\ApiCallFailedException;
use Syntax\SteamApi\Facades\SteamApi as Steam;
use Zeropingheroes\Lanager\SteamApp;
use bandwidthThrottle\tokenBucket\Rate;
use bandwidthThrottle\tokenBucket\TokenBucket;
use bandwidthThrottle\tokenBucket\BlockingConsumer;
use bandwidthThrottle\tokenBucket\storage\FileStorage;

class UpdateSteamAppsMetadata extends Command
{
    /**
     * Set command signature and description
     */
    public function __construct()
    {
        $this->signature = 'lanager:update-steam-apps-metadata'
                         . '{--all-apps : ' . __('phrase.update-all-apps') . '}';
        $this->description = __('phrase.update-steam-apps-metadata');

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!SteamApp::count()) {
            $this->error(__('phrase.database-empty-aborting'));
            return;
        }

        // Get apps which do not have a type set
        $steamAppIds = SteamApp::whereNull('type')->pluck('id')->toArray();
        if ( $this->option('all-apps')) {
            // Get all apps
            $steamAppIds = SteamApp::all()->pluck('id')->toArray();
        } else {
            // Get apps which do not have a type set
            $steamAppIds = SteamApp::whereNull('type')->pluck('id')->toArray();
        }

        if (!count($steamAppIds)) {
            $this->info(__('phrase.steam-app-metadata-up-to-date'));
            return;
        }

        $this->info(__('phrase.requesting-metadata-for-x-apps-from-steam-api', ['x' => count($steamAppIds)]));

        $progress = $this->output->createProgressBar(count($steamAppIds));
        $progress->setFormat("%current%/%max% %bar% %percent%%");

        // Prevent hitting Steam's API rate limits of 200 requests every 5 minutes
        $storage = new FileStorage(__DIR__ . "/../../../storage/app/api.bucket"); // store state in storage directory
        $rate = new Rate(40, Rate::MINUTE); // add 40 tokens every minute (= 200 over 5 minutes)
        $bucket = new TokenBucket(10, $rate, $storage); // bucket can never have more than 10 tokens saved up
        $consumer = new BlockingConsumer($bucket); // if no tokens are available, block further execution until there are tokens
        $bucket->bootstrap(10); // fill the bucket with 10 tokens initially

        $updatedCount = 0;
        foreach ($steamAppIds as &$appId) {
            // Query Steam API to get app details
            try {
                $consumer->consume(1);
                $app = Steam::app()->appDetails($appId);

                // If the API call failed, empty the bucket and skip the app
            } catch (ApiCallFailedException $e) {
                $consumer->consume(10);
                $progress->advance();
                continue;
            }
            if (isset($app[0])) {
                $type = $app[0]->type ?? null;
                $dlcs = $app[0]->dlc ?? [];
                $movies = $app[0]->movies ?? [];

                // If the app has a list of app IDs that are DLC, update their type now and remove them from the loop
                if (count($dlcs)) {
                    foreach ($dlcs as $dlcAppId) {
                        SteamApp::where('id', $dlcAppId)->update(['type' => 'dlc']);
                        $updatedCount++;
                        $key = array_search($dlcAppId, $steamAppIds);
                        if (false !== $key) {
                            unset($steamAppIds[$key]);
                            $progress->advance();
                        }
                    }
                }
                // If the app has a list of app IDs that are movies, update their type now and remove them from the loop
                if (count($movies)) {
                    foreach ($movies as $movie) {
                        SteamApp::where('id', $movie->id)->update(['type' => 'movie']);
                        $updatedCount++;
                        $key = array_search($movie->id, $steamAppIds);
                        if (false !== $key) {
                            unset($steamAppIds[$key]);
                            $progress->advance();
                        }
                    }
                }
            } else {
                $type = 'unknown';
            }
            SteamApp::where('id', $appId)->update(['type' => $type]);
            $updatedCount++;
            $progress->advance();
        }
        // Unset variable passed by reference
        unset($appId);
        $progress->finish();

        $this->info(PHP_EOL . __('phrase.x-steam-apps-updated', ['x' => $updatedCount]));
    }
}
