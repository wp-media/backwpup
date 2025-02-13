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
use OpenCloud\Common\Resource\PersistentResource;
use OpenCloud\Compute\Constants\Network as NetworkConst;
use OpenCloud\Compute\Service;
use OpenCloud\Networking\Resource\NetworkInterface;

/**
 * The Network class represents a single virtual network
 */
class Network extends PersistentResource implements NetworkInterface
{
    public $id;
    public $label;
    public $cidr;

    protected static $json_name = 'network';
    protected static $url_resource = 'os-networksv2';
    protected static $openStackResourcePath = 'os-networks';

    /**
     * Creates a new isolated Network object
     *
     * NOTE: contains hacks to recognize the Rackspace public and private
     * networks. These are not really networks, but they show up in lists.
     *
     * @param \OpenCloud\Compute\Service $service The compute service associated with
     *                                            the network
     * @param string|null                $id      The ID of the network (this handles the pseudo-networks
     *                                            Network::RAX_PUBLIC and Network::RAX_PRIVATE
     * @return Network
     */
    public function __construct(Service $service, $id = null)
    {
        $this->id = $id;

        switch ($id) {
            case NetworkConst::RAX_PUBLIC:
                $this->label = 'public';
                $this->cidr = 'NA';
                break;
            case NetworkConst::RAX_PRIVATE:
                $this->label = 'private';
                $this->cidr = 'NA';
                break;
            default:
                return parent::__construct($service, $id);
        }

        return;
    }

    /**
     * Always throws an error; updates are not permitted
     *
     * @throws Exceptions\NetworkUpdateError always
     */
    public function update($params = array())
    {
        throw new Exceptions\NetworkUpdateError('Isolated networks cannot be updated');
    }

    /**
     * Deletes an isolated network
     *
     * @api
     * @return \OpenCloud\HttpResponse
     * @throws NetworkDeleteError if HTTP status is not Success
     */
    public function delete()
    {
        switch ($this->id) {
            case NetworkConst::RAX_PUBLIC:
            case NetworkConst::RAX_PRIVATE:
                throw new Exceptions\DeleteError('Network may not be deleted');
            default:
                return parent::delete();
        }
    }

    /**
     * returns the visible name (label) of the network
     *
     * @api
     * @return string
     */
    public function name()
    {
        return $this->label;
    }

    /**
     * Creates the JSON object for the Create() method
     */
    protected function createJson()
    {
        return (object) array(
            'network' => (object) array(
                    'cidr'  => $this->cidr,
                    'label' => $this->label
                )
        );
    }

    /**
     * Rackspace Cloud Networks operates on a different URI than OpenStack Neutron.
     * {@inheritDoc}
     */
    public function getUrl($path = null, array $query = array())
    {
        if (!$url = $this->findLink('self')) {
            $url = $this->getParent()->getUrl($this->getResourcePath());

            if (null !== ($primaryKey = $this->getProperty($this->primaryKeyField()))) {
                $url->addPath($primaryKey);
            }
        }

        if (!$url instanceof Url) {
            $url = Url::factory($url);
        }

        return $url->addPath($path)->setQuery($query);
    }

    /**
     * Ascertain the correct URI path.
     *
     * @return string
     */
    public function getResourcePath()
    {
        if (strpos((string) $this->getService()->getUrl(), 'rackspacecloud.com') !== false) {
            return self::$url_resource;
        } else {
            return self::$openStackResourcePath;
        }
    }

    public function getId()
    {
        return $this->id;
    }
}
