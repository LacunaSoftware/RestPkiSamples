<?php


// Returns the verification code associated with the given document, or null if no verification code has been associated
// with it.
function getVerificationCode($fileId)
{
    // Initialize or resume session.
    session_start();

    // >>>>> NOTICE <<<<<
    // This should be implemented on your application as a SELECT on your "document table" by the ID of the document,
    // returning the value of the verification code column.
    if (isset($_SESSION['Files/' . $fileId . '/Code'])) {
        return $_SESSION['Files/' . $fileId . '/Code'];
    }
    return null;
}

// Registers the verification code for a given document.
function setVerificationCode($fileId, $code)
{
    // Initialize or resume session.
    session_start();

    // >>>>> NOTICE <<<<<
    // This should be implemented on your application as a UPDATE on your "document table" filling the verification
    // code column, which should be an indexed column.
    $_SESSION['Files/' . $fileId . '/Code'] = $code;
    $_SESSION['Codes/' . $code] = $fileId;
}

// Returns the ID of the document associated with a given verification code, or null if no document matches the given
// code.
function lookupVerificationCode($code)
{
    if (empty($code)) {
        return null;
    }

    // Initialize or resume session
    session_start();

    // >>>>> NOTICE <<<<<
    // This should be implemented on your application as a SELECT on your "document table" by the verification code
    // column, which should be an indexed column.
    if (isset($_SESSION['Codes/' . $code])) {
        return $_SESSION['Codes/' . $code];
    }
    return null;
}


