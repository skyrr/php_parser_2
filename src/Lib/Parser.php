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
    private $eventtarget2;
    private $viewstate2;
    private $viewstategenerator2;
    private $href;


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
        var_dump($response->getStatusCode());
        return $this;
    }

    public function parseAllResume()
    {
        sleep(3);
        $page = self::RESUME_URL;
        //       for ($i = 1; $i <= 1000; $i++) {
        $html = new Htmldom($page);// . $i);
                //var_dump($html);
                foreach($html->find('.cvitem') as $element){
                        $href = $element->find('a', 0)->href;
//                        $this->href = $element->find('a', 0)->href;
                    $href = '/cv/8223596';
                        $this->parseResume($href);
                    //    var_dump($href);
                    break;
                    }
        //        }
            }

    public function parseResume($uri)
    {
        //$response = $this->client->get(self::RESUME_URL);
        var_dump("////////////////////////////////////////////////////////////////////");
        //var_dump($response->getStatusCode());

        //$user_id = preg_replace("/^\/resumes\/(\d+)\/$/", "$1", $uri);
//        $response = $this->client->get(self::BASE_URL . $this->href);
        $response = $this->client->get(self::BASE_URL . $uri);
        var_dump(self::BASE_URL . $uri);
        /*$myfile = fopen("newfile.html", "w") or die("Unable to open file!");
        $txt = $response->getBody()->getContents();
        fwrite($myfile, $txt);
        fclose($myfile);*/
        $dom = new Htmldom($response->getBody());
        $this->eventtarget2 = 'ctl00$centerZone$BriefResume1$CvView1$cvHeader$lnkBuyCv';
        $this->eventargument = $dom->find('input[name=__EVENTARGUMENT]', 0)->value;
        $this->viewstate2 = $dom->find('input[name=__VIEWSTATE]', 0)->value;
        $this->viewstategenerator = $dom->find('input[name=__VIEWSTATEGENERATOR]', 0)->value;
        sleep(3);
//        $response = $this->client->post(self::BASE_URL . $this->href, [
        $response = $this->client->post(self::BASE_URL . $uri, [
            'form_params' => [
                '__EVENTTARGET' => $this->eventtarget2,
                '__EVENTARGUMENT' => $this->eventargument,
                '__VIEWSTATE' => $this->viewstate2,
                '__VIEWSTATEGENERATOR' => $this->viewstategenerator2,
                'ctl00$centerZone$BriefResume1$CvView1$cvHeader$AjaxLogin1$txtLogin' => $this->email,
                'ctl00$centerZone$BriefResume1$CvView1$cvHeader$AjaxLogin1$txtPassword' => $this->password,
                'ctl00$centerZone$BriefResume1$CvView1$cvHeader$hdnPrev' => 'http://rabota.ua/employer/find/cv_list?period=7&sort=score&pg=2',
                'ctl00$centerZone$ZoneLogin$chBoxRemember' => 'on'
            ],
        ]);
        //var_dump($response->getBody()->getContents());

        $dom = new Htmldom($response->getBody());
        $email = $dom->find('#centerZone_BriefResume1_CvView1_cvHeader_lblEmailValue', 0)->plaintext;
        $phone = $dom->find('#centerZone_BriefResume1_CvView1_cvHeader_lblPhoneValue', 0)->plaintext;
        //$response = json_decode($response->getBody()->getContents());
//                if ($response->status <> 'ok') {
//                    return;
//                }

//        $phone = $response->contact->phone_prim;
//        $email = $response->contact->email;

        // check database for duplications
        // SELECT url, COUNT(*) c FROM resume GROUP BY url HAVING c > 1

        $resume = new Resume();
        $resume->create([
            "url" => $uri,
            "phone" => $phone,
            "email" => $email
        ]);
//
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