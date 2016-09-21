<?php

namespace Lib;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Model\Resume;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Yangqi\Htmldom\Htmldom;

class Parser
{
    const BASE_URL = 'http://rabota.ua';
    const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.92 Safari/537.36';
    const LOGIN_URL = self::BASE_URL . '/employer/login';
    //http://rabota.ua/employer/find/cv_list?period=7&sort=score&pg=1
    //http://rabota.ua/employer/find/cv_list?keywords=&regionid=0
    const RESUME_URL = self::BASE_URL . '/employer/find/cv_list?period=7&sort=score&pg=1';
    //const OPEN_DATA_URL = self::BASE_URL . '/_data/_ajax/resumes_selection.php';

    private $email;
    private $password;

    /**
     * @var $client Client
     */
    private $client;

    /**
     * @var $cookies CookieJar[]
     */
    private $cookies;
    private $eventtarget;
    private $eventargument;
    private $viewstate;
    private $viewstategenerator;


    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function auth()
    {
        $response = $this->client->get(self::LOGIN_URL);
        $dom = new Htmldom($response->getBody());
        $this->eventtarget = 'ctl00$centerZone$ZoneLogin$btnLogin';
        $this->eventargument = $dom->find('input[name=__EVENTARGUMENT]', 0)->value;
        $this->viewstate = $dom->find('input[name=__VIEWSTATE]', 0)->value;
        $this->viewstategenerator = $dom->find('input[name=__VIEWSTATEGENERATOR]', 0)->value;

        sleep(3);
        $response = $this->client->post(self::LOGIN_URL, [
            'form_params' => [
                '__EVENTTARGET' => $this->eventtarget,
                '__EVENTARGUMENT' => $this->eventargument,
                '__VIEWSTATE' => $this->viewstate,
                '__VIEWSTATEGENERATOR' => $this->viewstategenerator,
                'ctl00$centerZone$ZoneLogin$txLogin' => $this->email,
                'ctl00$centerZone$ZoneLogin$txPassword' => $this->password,
                'ctl00$centerZone$ZoneLogin$chBoxRemember' => 'on'
            ],
        ]);

        return $this;
    }

    public function parseAllResume()
    {
        sleep(3);
        $page = self::RESUME_URL;
//       for ($i = 1; $i <= 1000; $i++) {
        $html = new Htmldom($page);// . $i);
        //var_dump($html);
        //foreach($html->find('div.') as $element){
            //    $href = $element->find('a', 0)->href;
//                $this->parseResume($href);
            //    var_dump($href);
            //break;
            //}
//        }
    }

    public function parseResume($uri)
    {
        $user_id = preg_replace("/^\/resumes\/(\d+)\/$/", "$1", $uri);
        sleep(3); // pause
//        $response = $this->client->post(self::OPEN_DATA_URL, [
//            'form_params' => [
//                'func' => 'showResumeContacts',
//                'id' => $user_id
//            ]
//        ]);
//
//        $response = json_decode($response->getBody()->getContents());
//        if ($response->status <> 'ok') {
//            return;
//        }
//
//        $phone = $response->contact->phone_prim;
//        $email = $response->contact->email;

        // check database for duplications
        // SELECT url, COUNT(*) c FROM resume GROUP BY url HAVING c > 1

        $phone = "some phone";
        $email = "some email";

        $resume = new Resume();

        $resume->create([
            "url" => self::BASE_URL . $uri,
            "phone" => $phone,
            "email" => $email
        ]);

        //$success = $resume->create();


    }

    public function createClient()
    {
        $handler_stack = new HandlerStack();
        $handler_stack->setHandler(new CurlHandler());
        $handler_stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $request = $request->withHeader('User-Agent', self::USER_AGENT);
            $request = $request->withHeader('Upgrade-Insecure-Requests', 1);
            $request = $request->withHeader('Host', 'rabota.ua');
            $request = $request->withHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8');

            if ($this->cookies) {
                $jar = new CookieJar(true, $this->cookies);
                $request = $jar->withCookieHeader($request);
            }

            //var_dump($request->getHeaders());

            return $request;
        }));
        $handler_stack->push(Middleware::mapResponse(function (ResponseInterface $response) {

            $set_cookie = $response->getHeader('Set-Cookie');
            if ($set_cookie) {
                foreach ($set_cookie as $item) {
                    $set_cookie = SetCookie::fromString($item);
                    $set_cookie->setDomain('.rabota.ua');
                    $this->cookies[] = $set_cookie;
                }
            }

            return $response;
        }));

        $this->client = new Client(['cookies' => true, 'handler' => $handler_stack]);
        return $this;
    }
}
