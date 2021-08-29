<?php

declare(strict_types=1);

namespace DaveKok\Stream;

/**
 * A simple implementation of the stream interface using PHP builtin functions.
 */
class Stream
{
    public readonly $handle;
    public readonly $pid;

    public function __destruct()
    {
        if ($this->pid) {
            posix_kill($this->pid, SIGTERM);
            pcntl_waitpid($this->pid, $status);
        }
    }

    /**
     * Open the stream.
     *
     * @param $url      where to open the stream to
     * @param $mode     in which mode to open
     * @param $context  the context to use
     *
     * File modes:
     * 'r'  Open for reading only; place the file pointer at the beginning of the file.
     * 'r+' Open for reading and writing; place the file pointer at the beginning of the file.
     * 'w'  Open for writing only; place the file pointer at the beginning of the file and truncate
     *      the file to zero length. If the file does not exist, attempt to create it.
     * 'w+' Open for reading and writing; place the file pointer at the beginning of the file and
     *      truncate the file to zero length. If the file does not exist, attempt to create it.
     * 'a'  Open for writing only; place the file pointer at the end of the file. If the file does
     *      not exist, attempt to create it. In this mode, fseek() has no effect, writes are always
     *      appended.
     * 'a+' Open for reading and writing; place the file pointer at the end of the file. If the file
     *      does not exist, attempt to create it. In this mode, fseek() only affects the reading
     *      position, writes are always appended.
     * 'x'  Create and open for writing only; place the file pointer at the beginning of the file.
     *      If the file already exists, the fopen() call will fail by returning FALSE and generating
     *      an error of level E_WARNING. If the file does not exist, attempt to create it. This is
     *      equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
     * 'x+' Create and open for reading and writing; otherwise it has the same behavior as 'x'.
     * 'c'  Open the file for writing only. If the file does not exist, it is created. If it exists,
     *      it is neither truncated (as opposed to 'w'), nor the call to this function fails (as is
     *      the case with 'x'). The file pointer is positioned on the beginning of the file. This may
     *      be useful if it's desired to get an advisory lock (see flock()) before attempting to modify
     *      the file, as using 'w' could truncate the file before the lock was obtained (if truncation
     *      is desired, ftruncate() can be used after the lock is requested).
     * 'c+' Open the file for reading and writing; otherwise it has the same behavior as 'c'.
     * 'e'  Set close-on-exec flag on the opened file descriptor. Only available in PHP compiled on
     *      POSIX.1-2008 conform systems.
     *
     * Socket modes for active sockets (client):
     * 'c'  Connect to server (STREAM_CLIENT_CONNECT).
     * 'a'  Connect asynchronous to server (STREAM_CLIENT_ASYNC_CONNECT).
     * 'p'  Persist connection (STREAM_CLIENT_PERSISTENT).
     * 'tX' Timeout, replace X with a float.
     *
     * Example: `open("tcp://127.0.0.1:80", "c");`
     *
     * Modes for exec scheme:
     * 'r'  Open command for reading only, reads from the stream will be the output of the process.
     * 'r+' Open command for reading/writing, same as 'r' but you can also write to the process.
     * 'w'  Open command for writing only, writes to the stream will be the input of the process.
     * 'w+' Same as r+
     * 'e'  stderr of the process is redirected to stdout of the process.
     *
     * Examples:
     * - `open("exec:ls?-l#WD=/home", "r");`
     * - `open("exec:cp?/home/src&/home/dest#WD=/home", "r");`
     * - `open("exec:/opt/package/command?arg1#WD=/opt/package", "r+e");`
     *
     * Use the fragment to set environment variables. WD is the working directory in which to execute the command.
     */
    public function open(string $url, string $mode = null, $context = null): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (in_array($scheme, stream_get_transports())) {

            $host = parse_url($url, PHP_URL_HOST);
            if ($mode) {
                $flags = strpos($mode, "c") !== false ? STREAM_CLIENT_CONNECT : 0;
                $flags|= strpos($mode, "a") !== false ? STREAM_CLIENT_ASYNC_CONNECT : 0;
                $flags|= strpos($mode, "p") !== false ? STREAM_CLIENT_PERSISTENT : 0;
                $timeout = strpos($mode, "t");
                if ($timeout !== false) {
                    $timeout = (float)substr($mode, $timeout+1, strspn($mode, "0123456789.", $timeout+1));
                } else {
                    $timeout = ini_get("default_socket_timeout");
                }
                if ($flags == 0) {
                    $flags = STREAM_CLIENT_CONNECT;
                }
            } else {
                $mode ??= "c";
            }
            if ($context === null) {
                $this->handle = stream_socket_client($url, $errno, $errstr, $timeout, $flags);
            } else {
                $this->handle = stream_socket_client($url, $errno, $errstr, $timeout, $flags, $context);
            }
            if ($this->handle === false) {
                throw new OpenStreamError($errstr, $errno);
            }

        } else if ($scheme == "exec") {

            $mode ??= "r";
            $exec = parse_url($url);
            $args = explode("&", $exec["query"] ?? "");
            foreach($args as &$arg)($arg = urldecode($arg));
            parse_str($exec["fragment"]??"", $env);
            $wd = $env["WD"] ?? null;
            unset($env["WD"]);

            $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
            if ($pair === false) {
                throw new OpenStreamError("Unable to create socket pair.");
            }

            $this->pid = pcntl_fork();

            if ($this->pid === -1) {

                throw new OpenStreamError("Failed to fork.");

            } else if ($this->pid === 0) {

                // the child process

                // redirect stdin, stdout and stderr
                if (strpos($mode, "w") !== false || strpos($mode, "r+") !== false) {
                    fclose(STDIN);
                    $GLOBALS["STDIN"] = fopen("php://fd/".((int)$pair[1]));
                } else {
                    fclose(STDIN);
                    $GLOBALS["STDIN"] = fopen("/dev/zero", "r");
                }
                if (strpos($mode, "r") !== false || strpos($mode, "w+") !== false) {
                    fclose(STDOUT);
                    $GLOBALS["STDOUT"] = fopen("php://fd/".((int)$pair[1]));
                    if (strpos($mode, "e") !== false) {
                        fclose(STDERR);
                        $GLOBALS["STDERR"] = fopen("php://fd/".((int)$pair[1]));
                    } else {
                        fclose(STDERR);
                        $GLOBALS["STDERR"] = fopen("/dev/null", "w");
                    }
                } else {
                    fclose(STDOUT);
                    $GLOBALS["STDOUT"] = fopen("/dev/null", "w");
                    fclose(STDERR);
                    $GLOBALS["STDERR"] = fopen("/dev/null", "w");
                }
                fclose($pair[0]);
                fclose($pair[1]);

                // run program
                chdir($wd);
                if ($exec["path"][0] != "/") { // if path is not absolute try search path
                    foreach (explode(":", getenv("PATH") ?: "") as $path) {
                        $file = $path . "/" . $exec["path"];
                        if (is_executable($file)) {
                            pcntl_exec($file, $args, $env);
                            echo "Unable to run $file.\n";
                            exit(1);
                        }
                    }
                    echo "{$exec["path"]} not found.\n";
                } else {
                    if (is_executable($file)) {
                        pcntl_exec($exec["path"], $args, $env);
                        echo "Unable to run {$exec["path"]}.\n";
                    } else {
                        echo "{$exec["path"]} is not executable.\n";
                    }
                }
                exit(1);

            } else {

                // the parent process
                fclose($pair[1]);
                $this->handle = $pair[0];

            }

        } else {

            $mode ??= "r";
            $this->handle = fopen($url, $mode, false, $context);
            if ($this->handle === false) {
                throw new OpenStreamError("Unable to open stream for $url with mode $mode.");
            }

        }
    }

    public function read(int $length): string
    {
        $buffer = fread($this->handle, $length);
        if (false === $buffer) {
            throw new ReadStreamError();
        }
        return $buffer;
    }

    public function readLine(int $length, string $ending = "\n"): string
    {
        $buffer = stream_get_line($this->handle, $length, $ending);
        if (false === $buffer) {
            throw new ReadStreamError();
        }
        return $buffer;
    }

    public function readCSV(int $length = 0, string $delimiter = ",", string $enclosure = '"', string $escape = "\\"): array
    {
        $buffer = fgetcsv($this->handle, $length, $delimiter, $enclosure, $escape);
        if (false === $buffer) {
            throw new ReadStreamError();
        }
        return $buffer;
    }

    public function readAll(int $maxlength = -1, int $offset = -1): string
    {
        $buffer = stream_get_contents($this->handle, $maxlength, $offset);
        if (false === $buffer) {
            throw new ReadStreamError();
        }
        return $buffer;
    }

    public function receive(int $length, int $flags = 0, string &$address = null): string
    {
        if ($address !== null) {
            $buffer = stream_socket_recvfrom($this->handle, $length, $flags, $address);
        } else {
            $buffer = stream_socket_recvfrom($this->handle, $length, $flags);
        }
        if (false === $buffer) throw new ReadStreamError();
        return $buffer;
    }

    public function write(string $text, int $length = null): int
    {
        if ($length !== null) {
            $ret = fwrite($this->handle, $text, $length);
        } else {
            $ret = fwrite($this->handle, $text);
        }
        if (false === $ret) throw new WriteStreamError();
        return $ret;
    }

    public function writeLine(string $text): int
    {
        $ret = fwrite($this->handle, "$text\n");
        if (false === $ret) throw new WriteStreamError();
        return $ret;
    }

    public function writeCSV(array $fields, string $delimiter = ",", string $enclosure = '"', string $escape = "\\"): int
    {
        $ret = fputcsv($this->handle, $fields, $delimiter, $enclosure, $escape);
        if (false === $ret) throw new WriteStreamError();
        return $ret;
    }

    public function send(string $data, int $flags = 0, string $address = null): string
    {
        if ($address !== null) {
            $buffer = stream_socket_sendto($this->handle, $data, $flags, $address);
        } else {
            $buffer = stream_socket_sendto($this->handle, $data, $flags);
        }
        if (false === $buffer) throw new WriteStreamError();
        return $buffer;
    }

    public function truncate(int $size = 0): void
    {
        if (false === ftruncate($this->handle, $size)) throw new StreamError();
    }

    /**
     * Let the stream flow, either to the default output stream
     * or to the given stream.
     *
     * @param self|null $dest  the destination stream
     * @return int  the number of bytes that flowed.
     */
    public function flow(?self $dest = null): int
    {
        if ($dest) {
            $ret = stream_copy_to_stream($this->handle, $dest->stream);
        } else {
            $ret = fpassthru($this->handle);
        }
        if (false === $ret) {
            throw new StreamFlowError();
        }
        return $ret;
    }

    public function close(): void
    {
        $ret = fclose($this->handle);
        if ($ret === false) {
            throw new StreamCloseError("Failed to close stream.");
        }
    }

    public function shutdown(int $how): void
    {
        if (stream_socket_shutdown($this->handle, $how) === false)
            throw new StreamError();
    }

    public function setBlocking(bool $block): void
    {
        if (stream_set_blocking($this->handle, $block) === false)
            throw new StreamError();
    }

    public function setChunkSize(int $size): int
    {
        $ret = stream_set_chunk_size($this->handle, $size);
        if ($ret === false)
            throw new StreamError();
        return $ret;
    }

    public function setReadBufferSize(int $size): int
    {
        return stream_set_read_buffer($this->handle, $size);
    }

    public function setWriteBufferSize(int $size): int
    {
        return stream_set_write_buffer($this->handle, $size);
    }

    public function setTimeout(int $timeout, int $microseconds = -1): void
    {
        if (stream_set_timeout($this->handle, $timeout, $microseconds) === false)
            throw new StreamError();
    }

    public function eof(): bool
    {
        return feof($this->handle);
    }

    public function tell(): int
    {
        $pos = ftell($this->handle);
        if ($pos === false) throw new StreamError();
        return $pos;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (fseek($this->handle, $offset, $whence) === -1)
            throw new StreamError();
    }

    public function flush(): void
    {
        if (fflush($this->handle) === false)
            throw new WriteStreamError();
    }

    public function lock(int $operation, int &$wouldblock = null): void
    {
        if (flock($this->handle, $operation, $wouldblock) === false)
            throw new StreamError();
    }

    public function supportsLocking(): bool
    {
        return stream_supports_lock($this->handle);
    }

    public function status(): array
    {
        return fstat($this->handle);
    }

    public function meta(): array
    {
        return stream_get_meta_data($this->handle);
    }

    public function getName(bool $want_peer = true): string
    {
        $ret = stream_socket_get_name($this->handle, $want_peer);
        if ($ret === false)
            throw new StreamError();
        return $ret;
    }

    public function isTTY(): bool
    {
        return stream_isatty($this->handle);
    }

    public function isLocal(): bool
    {
        return stream_is_local($this->handle);
    }

    public function tls(bool $enable, int $crypto_type = null, ?self $reference = null): bool
    {
        if ($reference !== null) {
            $ret = stream_socket_enable_crypto(
                $this->handle,
                $enable,
                $crypto_type,
                $reference->stream
            );
        } else if ($crypto_type !== null) {
            $ret = stream_socket_enable_crypto(
                $this->handle,
                $enable,
                $crypto_type
            );
        } else {
            $ret = stream_socket_enable_crypto($this->handle, $enable);
        }
        if ($ret === false)
            throw new StreamError();
        if ($ret === 0)
            return false;
        return true;
    }
}
