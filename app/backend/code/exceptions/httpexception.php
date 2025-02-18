<?php

declare(strict_types=1);

namespace megaraid\exceptions;

class httpException extends \Exception {

    protected int $http_error = 0;

    public function __construct(int $httperror, string $message = "", int $code = 0, ?\Throwable $previous = null) {
        $this->http_error = $httperror;
        parent::__construct($message, $code, $previous);
    }

    public function getHttpError() { return $this->http_error; }

    public function getHeader() {

        $header = "HTTP/1.1 " . $this->http_error . " ";

        switch($this->http_error) {

            case 100: $header .= "Continue"; break;
            case 101: $header .= "Switching Protocols"; break;
            case 102: $header .= "Processing"; break;
            case 103: $header .= "Early Hints"; break;

            case 200: $header .= "OK"; break;
            case 201: $header .= "Created"; break;
            case 202: $header .= "Accepted"; break;
            case 203: $header .= "Non-Authoritative Information"; break;
            case 204: $header .= "No Content"; break;
            case 205: $header .= "Reset Content"; break;
            case 206: $header .= "Partial Content"; break;
            case 207: $header .= "Multi-Status"; break;
            case 208: $header .= "Already Reported"; break;
            case 210: $header .= "Content Different"; break;
            case 226: $header .= "IM Used"; break;

            case 300: $header .= "Multiple Choices"; break;
            case 301: $header .= "Moved Permanently"; break;
            case 302: $header .= "Found"; break;
            case 303: $header .= "See Other"; break;
            case 304: $header .= "Not Modified"; break;
            case 305: $header .= "Use Proxy"; break;
            case 306: $header .= "Switch Proxy"; break;
            case 307: $header .= "Temporary Redirect"; break;
            case 308: $header .= "Permanent Redirect"; break;
            case 310: $header .= "Too many Redirects"; break;

            case 400: $header .= "Bad Request"; break;
            case 401: $header .= "Unauthorized"; break;
            case 402: $header .= "Payment Required"; break;
            case 403: $header .= "Forbidden"; break;
            case 404: $header .= "Not Found"; break;
            case 405: $header .= "Method Not Allowed"; break;
            case 406: $header .= "Not Acceptable"; break;
            case 407: $header .= "Proxy Authentication Required"; break;
            case 408: $header .= "Request Time-out"; break;
            case 409: $header .= "Conflict"; break;
            case 410: $header .= "Gone"; break;
            case 411: $header .= "Length Required"; break;
            case 412: $header .= "Precondition Failed"; break;
            case 413: $header .= "Request Entity Too Large"; break;
            case 414: $header .= "Request-URI Too Long"; break;
            case 415: $header .= "Unsupported Media Type"; break;
            case 416: $header .= "Requested range unsatisfiable"; break;
            case 417: $header .= "Expectation failed"; break;
            case 418: $header .= "I'm a teapot"; break;
            case 419: $header .= "Page expired"; break;
            case 421: $header .= "Bad mapping / Misdirected Request"; break;
            case 422: $header .= "Unprocessable entity"; break;
            case 423: $header .= "Locked"; break;
            case 424: $header .= "Method failure"; break;
            case 425: $header .= "Too Early"; break;
            case 426: $header .= "Upgrade Required"; break;
            case 427: $header .= "Invalid digital signature"; break;
            case 428: $header .= "Precondition Required"; break;
            case 428: $header .= "Too Many Requests"; break;
            case 431: $header .= "Request Header Fields Too Large"; break;
            case 449: $header .= "Retry With"; break;
            case 450: $header .= "Blocked by Windows Parental Controls"; break;
            case 451: $header .= "Unavailable For Legal Reasons"; break;
            case 456: $header .= "Unrecoverable Error"; break;

            case 498: $header .= "Token expired/invalid"; break;

            case 500: $header .= "Internal Server Error"; break;
            case 501: $header .= "Not Implemented"; break;
            case 502: $header .= "Bad Gateway ou Proxy Error"; break;
            case 503: $header .= "Service Unavailable"; break;
            case 504: $header .= "Gateway Time-out"; break;
            case 505: $header .= "HTTP Version not supported"; break;
            case 506: $header .= "Variant Also Negotiates"; break;
            case 507: $header .= "Insufficient storage"; break;
            case 508: $header .= "Loop detected"; break;
            case 509: $header .= "Bandwidth Limit Exceeded"; break;
            case 510: $header .= "Not extended"; break;
            case 511: $header .= "Network authentication required"; break;

            default:
                $header .= "Unknown HTTP Status Code";
            
        }

        //return "HTTP/1.1 200 OK";
        return $header;

    }

}

?>