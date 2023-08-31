<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Verify\V2\Service;

use Twilio\Options;
use Twilio\Values;

abstract class RateLimitOptions {
    /**
     * @param string $description Description of this Rate Limit
     * @return CreateRateLimitOptions Options builder
     */
    public static function create($description = Values::NONE) {
        return new CreateRateLimitOptions($description);
    }

    /**
     * @param string $description Description of this Rate Limit
     * @return UpdateRateLimitOptions Options builder
     */
    public static function update($description = Values::NONE) {
        return new UpdateRateLimitOptions($description);
    }
}

class CreateRateLimitOptions extends Options {
    /**
     * @param string $description Description of this Rate Limit
     */
    public function __construct($description = Values::NONE) {
        $this->options['description'] = $description;
    }

    /**
     * Description of this Rate Limit
     *
     * @param string $description Description of this Rate Limit
     * @return $this Fluent Builder
     */
    public function setDescription($description) {
        $this->options['description'] = $description;
        return $this;
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString() {
        $options = array();
        foreach ($this->options as $key => $value) {
            if ($value != Values::NONE) {
                $options[] = "$key=$value";
            }
        }
        return '[Twilio.Verify.V2.CreateRateLimitOptions ' . \implode(' ', $options) . ']';
    }
}

class UpdateRateLimitOptions extends Options {
    /**
     * @param string $description Description of this Rate Limit
     */
    public function __construct($description = Values::NONE) {
        $this->options['description'] = $description;
    }

    /**
     * Description of this Rate Limit
     *
     * @param string $description Description of this Rate Limit
     * @return $this Fluent Builder
     */
    public function setDescription($description) {
        $this->options['description'] = $description;
        return $this;
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString() {
        $options = array();
        foreach ($this->options as $key => $value) {
            if ($value != Values::NONE) {
                $options[] = "$key=$value";
            }
        }
        return '[Twilio.Verify.V2.UpdateRateLimitOptions ' . \implode(' ', $options) . ']';
    }
}