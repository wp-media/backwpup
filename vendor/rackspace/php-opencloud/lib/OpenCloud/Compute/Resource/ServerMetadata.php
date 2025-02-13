<?php
/**
 * Copyright 2012-2014 Rackspace US, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCloud\Compute\Resource;

use Guzzle\Http\Url;
use OpenCloud\Common\Exceptions;
use OpenCloud\Common\Http\Message\Formatter;
use OpenCloud\Common\Lang;
use OpenCloud\Common\Metadata;

/**
 * This class handles specialized metadata for OpenStack Server objects (metadata
 * items can be managed individually or in aggregate).
 *
 * Server metadata is a weird beast in that it has resource representations
 * and HTTP calls to set the entire server metadata as well as individual
 * items.
 */
class ServerMetadata extends Metadata
{
    private $parent;
    protected $key; // the metadata item (if supplied)
    private $url; // the URL of this particular metadata item or block

    /**
     * Constructs a Metadata object associated with a Server or Image object
     *
     * @param object $parent either a Server or an Image object
     * @param string $key    the (optional) key for the metadata item
     * @throws MetadataError
     */
    public function __construct(Server $parent, $key = null)
    {
        // construct defaults
        $this->setParent($parent);

        // set the URL according to whether or not we have a key
        if ($this->getParent()->getId()) {
            $this->url = $this->getParent()->url('metadata');
            $this->key = $key;

            // in either case, retrieve the data
            $response = $this->getParent()
                ->getClient()
                ->get($this->getUrl())
                ->send();

            // parse and assign the server metadata
            $body = Formatter::decode($response);

            if (isset($body->metadata)) {
                foreach ($body->metadata as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns the URL of the metadata (key or block)
     *
     * @return string
     * @param string $subresource not used; required for strict compatibility
     * @throws ServerUrlerror
     */
    public function getUrl($path = null, array $query = array())
    {
        if (!isset($this->url)) {
            throw new Exceptions\ServerUrlError(
                'Metadata has no URL (new object)'
            );
        }

        return Url::factory($this->url)->addPath($this->key);
    }

    /**
     * Sets a new metadata value or block
     *
     * Note that, if you're setting a block, the block specified will
     * *entirely replace* the existing block.
     *
     * @api
     * @return void
     * @throws MetadataCreateError
     */
    public function create()
    {
        return $this->getParent()
            ->getClient()
            ->put($this->getUrl(), self::getJsonHeader(), $this->getMetadataJson())
            ->send();
    }

    /**
     * Updates a metadata key or block
     *
     * @api
     * @return void
     * @throws MetadataUpdateError
     */
    public function update()
    {
        return $this->getParent()
            ->getClient()
            ->post($this->getUrl(), self::getJsonHeader(), $this->getMetadataJson())
            ->send();
    }

    /**
     * Deletes a metadata key or block
     *
     * @api
     * @return void
     * @throws MetadataDeleteError
     */
    public function delete()
    {
        return $this->getParent()->getClient()->delete($this->getUrl(), array());
    }

    public function __set($key, $value)
    {
        // if a key was supplied when creating the object, then we can't set
        // any other values
        if ($this->key && $key != $this->key) {
            throw new Exceptions\MetadataKeyError(sprintf(
                Lang::translate('You cannot set extra values on [%s]'),
                $this->getUrl()
            ));
        }

        // otherwise, just set it;
        parent::__set($key, $value);
    }

    /**
     * Builds a metadata JSON string
     *
     * @return string
     * @throws MetadataJsonError
     * @codeCoverageIgnore
     */
    private function getMetadataJson()
    {
        $object = (object) array(
            'meta'     => (object) array(),
            'metadata' => (object) array()
        );

        // different element if only a key is set
        if ($name = $this->key) {
            $object->meta->$name = $this->$name;
        } else {
            $object->metadata = $this->keylist();
        }

        $json = json_encode($object);
        $this->checkJsonError();

        return $json;
    }
}
