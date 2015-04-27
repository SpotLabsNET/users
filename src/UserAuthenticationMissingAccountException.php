<?php

namespace Users;

/**
 * Represents a user trying to login but no account was found with the
 * given credentials.
 */
class UserAuthenticationMissingAccountException extends UserAuthenticationException { }
