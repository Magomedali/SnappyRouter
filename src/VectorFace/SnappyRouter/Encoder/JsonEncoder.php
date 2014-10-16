<?php

namespace VectorFace\SnappyRouter\Encoder;

use VectorFace\SnappyRouter\Response\Response;
use VectorFace\SnappyRouter\Exception\EncoderException;

/**
 * Encodes the response in the JSON format.
 * @copyright Copyright (c) 2014, VectorFace, Inc.
 * @author Dan Bruce <dbruce@vectorface.com>
 */
class JsonEncoder extends AbstractEncoder
{
    /**
     * @param Response $response The response to be encoded.
     * @return (string) Returns the response encoded as a string.
     */
    public function encode(Response $response)
    {
        $responseObject = $response->getResponseObject();
        if (is_array($responseObject) || is_scalar($responseObject)) {
            return json_encode($responseObject);
        } elseif (is_object($responseObject)) {
            if (method_exists($responseObject, 'jsonSerialize')) {
                return json_encode($responseObject);
            } else {
                return json_encode(get_object_vars($responseObject));
            }
        } else {
            throw new EncoderException('Unable to encode as JSON.');
        }
    }
}
