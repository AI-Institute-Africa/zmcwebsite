<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\Request;

class RunUATCrawler extends Command
{
    protected $signature = 'uat:crawl';
    protected $description = 'Crawl all GET routes as different roles and report 500 errors.';

    public function handle()
    {
        $roles = [
            'super_admin', 'pr_officer', 'accreditation_officer'
        ];

        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return in_array('GET', $route->methods()) && !str_contains($route->uri(), '{');
        });

        $report = [];

        foreach ($roles as $role) {
            $user = User::role($role)->first();
            if (!$user) continue;

            foreach ($routes as $route) {
                $uri = $route->uri();
                if ($uri === 'logout' || str_contains($uri, 'sanctum')) continue;

                $request = Request::create($uri, 'GET');
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
                
                try {
                    app()->instance('request', $request);
                    $response = app()->handle($request);
                    $status = $response->getStatusCode();
                    
                    if ($status === 500) {
                        $content = $response->getContent();
                        preg_match('/<title>(.*?)<\/title>/', $content, $matches);
                        $errorMsg = $matches[1] ?? 'Unknown error';
                        // In Laravel, the first line of the exception is usually in the title of the error page
                        // Or if json:
                        $json = json_decode($content, true);
                        if ($json && isset($json['message'])) {
                            $errorMsg = $json['message'];
                        }
                        
                        $this->error("[$role] $uri -> HTTP $status - $errorMsg");
                        $report[] = "[$role] $uri -> HTTP $status - $errorMsg";
                    }
                } catch (\Exception $e) {
                    $this->error("[$role] $uri -> Exception: " . $e->getMessage());
                    $report[] = "[$role] $uri -> Exception: " . $e->getMessage();
                }
            }
        }

        if (empty($report)) {
            $this->info("\nUAT completed! No 500 errors found.");
        } else {
            $this->error("\nUAT failed. Found errors.");
        }
    }
}
