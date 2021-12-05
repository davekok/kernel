<?php

declare(strict_types=1);

namespace davekok\kernel;

enum OpenMode: string {
    case READ_ONLY            = "rbe";  // open for reading only, fail if it does not exists
    case READ_WRITE           = "cbe+"; // open for reading and writing, create if it does not exists
    case STRICT_READ_WRITE    = "rbe+"; // open for reading and writing, fail if it does not exists
    case WRITE_ONLY           = "cbe";  // open for writing only, create if it does not exists
    case APPEND_ONLY          = "abe";  // open for appending only, create if it does not exists
    case READ_APPEND          = "abe+"; // open for reading and appending, create if it does not exists
    case TRUNCATE_WRITE_ONLY  = "wbe";  // open for writing only, create if it does not exists, truncate if it does
    case TRUNCATE_READ_WRITE  = "wbe+"; // open for reading and writing, create if it does not exists, truncate if it does
    case CREATE_WRITE_ONLY    = "xbe";  // open for writing only, create if it does not exists, fail if it does
    case CREATE_READ_WRITE    = "xbe+"; // open for reading and writing, create if it does not exists, fail if it does
}
