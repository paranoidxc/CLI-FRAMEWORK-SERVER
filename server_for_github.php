<?php

$host = '0.0.0.0';
$port = 9000;
$listen_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

socket_set_option($listen_socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_set_option($listen_socket, SOL_SOCKET, SO_REUSEPORT, 1);

socket_bind($listen_socket, $host, $port);
socket_listen($listen_socket);
socket_set_nonblock($listen_socket);

socket_getsockname($listen_socket, $addr, $port);
echo 'Select HTTP Server - ' . $addr . ':' . $port . PHP_EOL;

$client = array($listen_socket);

for ($i = 0; $i <= 10; $i++) {
    $pid = pcntl_fork();

    if (0 == $pid) {
        while (true) {
            $read      = $client;
            $write     = array();
            $exception = array();
            $ret       = socket_select($read, $write, $exception, NULL);
            if ($ret <= 0) {
                continue;
            }
            if (in_array($listen_socket, $read)) {
                $connection_socket = socket_accept($listen_socket);
                if (!$connection_socket) {
                    continue;
                }
                socket_getpeername($connection_socket, $client_ip, $client_port);
                echo "Client {$client_ip}:{$client_port}:" . posix_getpid() . PHP_EOL;
                $client[] = $connection_socket;
                $key      = array_search($listen_socket, $read);
                unset($read[$key]);
            }
            foreach ($read as $read_key => $read_fd) {
                $ret = socket_recv($read_fd, $recv_content, 65535, 0);
                $decode_ret = request($recv_content);
                $path = $decode_ret['pathinfo'];
                $path = str_replace("/", "", $path);

                $script_php = 'index.php';
                if (in_array($path, ["a.php", "b.php", "c.php"])) {
                    $script_php = $path;
                }

                ob_start();
                require "./{$script_php}";
                $out = ob_get_contents();
                ob_end_clean();

                $res = response($out);
                socket_write($read_fd, $res, strlen($res));

                //socket_shutdown( $read_fd );
                socket_close($read_fd);
                unset($read[$read_key]);
                $key = array_search($read_fd, $client);
                unset($client[$read_key]);
            }
        }
    }
}

function request($s_raw_http_content)
{
    $s_http_method       = '';
    $s_http_version      = '';
    $s_http_pathinfo     = '';
    $s_http_querystring  = '';
    $s_http_body_boundry = '';  // 当post方法且为form-data的时候.
    $a_http_post         = array();
    $a_http_get          = array();
    $a_http_header       = array();
    $a_http_file         = array();
    list($s_http_line_and_header, $s_http_body) = explode("\r\n\r\n", $s_raw_http_content, 2);
    $a_http_line_header = explode("\r\n", $s_http_line_and_header);
    $s_http_line = $a_http_line_header[0];
    unset($a_http_line_header[0]);
    $a_http_raw_header = $a_http_line_header;
    list($s_http_method, $s_http_pathinfo_querystring, $s_http_version) = explode(' ', $s_http_line);
    if (false === strpos($s_http_pathinfo_querystring, "?")) {
        $s_http_pathinfo = $s_http_pathinfo_querystring;
    } else {
        list($s_http_pathinfo, $s_http_querystring) = explode('?', $s_http_pathinfo_querystring);
    }
    if ('' != $s_http_querystring) {
        $a_raw_http_get = explode('&', $s_http_querystring);
        foreach ($a_raw_http_get as $s_http_get_item) {
            if ('' != trim($s_http_get_item)) {
                list($s_get_key, $s_get_value) = explode('=', $s_http_get_item);
                $a_http_get[$s_get_key] = $s_get_value;
            }
        }
    }
    foreach ($a_http_raw_header as $a_raw_http_header_key => $a_raw_http_header_item) {
        if ('' != trim($a_raw_http_header_item)) {
            list($s_http_header_key, $s_http_header_value) = explode(":", $a_raw_http_header_item);
            $a_http_header[strtoupper($s_http_header_key)] = $s_http_header_value;
        }
    }

    $a_ret = array(
        'method'   => $s_http_method,
        'version'  => $s_http_version,
        'pathinfo' => $s_http_pathinfo,
        'post'     => $a_http_post,
        'get'      => $a_http_get,
        'header'   => $a_http_header,
    );
    return $a_ret;
}

function response($a_data)
{
    $s_data = "<html><body>" . $a_data . "</body></html>";
    $s_http_line   = "HTTP/1.1 200 OK";
    $a_http_header = array(
        "Content-Type"   => "text/html;charset=UTF-8",
        "Connection" => "keep-alive",
        "Date"           => gmdate("M d Y H:i:s", time()),
        "Server"   => "ftw",
        "Content-Length" => strlen($s_data),
    );
    $s_http_header = '';
    foreach ($a_http_header as $s_http_header_key => $s_http_header_item) {
        $_s_header_line = $s_http_header_key . ': ' . $s_http_header_item;
        $s_http_header  = $s_http_header . $_s_header_line . "\r\n";
    }
    $s_ret = $s_http_line . "\r\n" . $s_http_header . "\r\n" . $s_data;
    return $s_ret;
}

while (1) {
    sleep(1);
}
