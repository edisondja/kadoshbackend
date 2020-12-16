<?php

namespace App\Http\Middleware;
use \Firebase\JWT\JWT;


use Closure;

class TokenRead{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    protected $addHttpCookie = FALSE;


 
    public function handle($request, Closure $next)
    {


       // $decoded = JWT::decode($request->header('Authorization'),env("FIRMA_TOKEN"), array('HS256'))
        
        //print_r($decoded);

       /// $request->headers->set('Accept', 'application/json','Berear');


       // dd($request->bearerToken());


        //dd($request->input('token'));

    /*if($request->bearerToken()){
            
        JWT::decode($request->bearerToken(),env("FIRMA_TOKEN"),array('HS256'));
                
               return  $next($request);

    
    }else{


        return redirect("home");
    }*/


    
        dd($response->headers->set('Authorization', 'Bearer '.$response->bearerToken()));

        if (JWT::decode($request->bearerToken(),env("FIRMA_TOKEN"),array('HS256'))) {
            return redirect('home');
        }

        return $next($request);
    
        
     

        
    
        
    }
}
