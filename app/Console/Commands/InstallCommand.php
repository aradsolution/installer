<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup PMIS Software';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $key = $this->ask('Enter your lisence key');
        $licence = json_decode(file_get_contents('https://api.aradsolution.com/get/licence?key=' . urlencode($key)));
        if (!$licence || empty($licence->licence) || empty($licence->token) || empty($licence->packages)) {
            $this->error('The key is not valid or there is no relecant licence!');
            $this->error('Setup aborted!');
            exit;
        }

        $composer = json_decode(file_get_contents(realpath('composer.json')));
        if (!empty($composer->repositories[0])) {
            $composer->repositories[0]->options = (object) ['http' => ['auth-token: ' . $licence->token]];
            foreach ($composer->require as $package => $version) {
                if (substr($package, 0, 13) == 'aradsolution/') {
                    unset($composer->require->$package);
                }
            }
            foreach ($licence->packages as $package => $version) {
                $composer->require->$package = $version;
            }
            file_put_contents(realpath('composer.json'), json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('Almost done. Please execute "composer update" command...');
            chdir(realpath());
        } else {
            $this->error('composer.json is not valid!');
            $this->error('Setup aborted!');
        }
    }
}
