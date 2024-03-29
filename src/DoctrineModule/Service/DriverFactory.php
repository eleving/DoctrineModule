<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace DoctrineModule\Service;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Doctrine\Common\Annotations;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\Driver\DefaultFileLocator;
use DoctrineModule\Options\Driver as DriverOptions;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * MappingDriver ServiceManager factory
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Kyle Spraggs <theman@spiffyjr.me>
 */
class DriverFactory extends AbstractFactory
{
    /**
     * {@inheritDoc}
     * @return MappingDriver
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var $options DriverOptions */
        $options = $this->getOptions($container, 'driver');

        return $this->createDriver($container, $options);
    }
    /**
     * {@inheritDoc}
     * @return MappingDriver
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, MappingDriver::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsClass()
    {
        return 'DoctrineModule\Options\Driver';
    }

    /**
     * @param  ContainerInterface $container
     * @param  DriverOptions      $options
     * @throws InvalidArgumentException
     * @return MappingDriver
     */
    protected function createDriver(ContainerInterface $container, DriverOptions $options)
    {
        $class = $options->getClass();

        if (!$class) {
            throw new InvalidArgumentException('Drivers must specify a class');
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Driver with type "%s" could not be found', $class));
        }

        // Not all drivers (DriverChain) require paths.
        $paths = $options->getPaths();

        // Special options for AnnotationDrivers.
        if ('Doctrine\Persistence\Mapping\Driver\AnnotationDriver' === $class
            || is_subclass_of($class, 'Doctrine\Persistence\Mapping\Driver\AnnotationDriver')
        ) {
            $reader = new Annotations\AnnotationReader;
            $reader = new Annotations\CachedReader(
                new Annotations\IndexedReader($reader),
                $container->get($options->getCache())
            );
            /* @var $driver MappingDriver */
            $driver = new $class($reader, $paths);
        } else {
            /* @var $driver MappingDriver */
            $driver = new $class($paths);
        }

        if ($options->getExtension() && $driver instanceof FileDriver) {
            /* @var $driver FileDriver */
            /* @var $locator \Doctrine\Persistence\Mapping\Driver\FileLocator */
            $locator = $driver->getLocator();

            if (get_class($locator) === 'Doctrine\Persistence\Mapping\Driver\DefaultFileLocator') {
                $driver->setLocator(new DefaultFileLocator($locator->getPaths(), $options->getExtension()));
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Discovered file locator for driver of type "%s" is an instance of "%s". This factory '
                        . 'supports only the DefaultFileLocator when an extension is set for the file locator',
                        get_class($driver),
                        get_class($locator)
                    )
                );
            }
        }

        // Extra post-create options for DriverChain.
        if ($driver instanceof MappingDriverChain && $options->getDrivers()) {
            /* @var $driver \Doctrine\Persistence\Mapping\Driver\MappingDriverChain */
            $drivers = $options->getDrivers();

            if (!is_array($drivers)) {
                $drivers = array($drivers);
            }

            foreach ($drivers as $namespace => $driverName) {
                if (null === $driverName) {
                    continue;
                }
                $options = $this->getOptions($container, 'driver', $driverName);
                $driver->addDriver($this->createDriver($container, $options), $namespace);
            }
        }

        return $driver;
    }
}
