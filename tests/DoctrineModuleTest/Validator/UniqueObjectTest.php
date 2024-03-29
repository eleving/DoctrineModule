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

namespace DoctrineModuleTest\Validator\Adapter;

use stdClass;
use PHPUnit_Framework_TestCase as BaseTestCase;
use DoctrineModule\Validator\UniqueObject;

/**
 * Tests for the UniqueObject validator
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Oskar Bley <oskar@programming-php.net>
 */
class UniqueObjectTest extends BaseTestCase
{
    public function testCanValidateWithNotAvailableObjectInRepository()
    {
        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('matchKey' => 'matchValue'))
            ->will($this->returnValue(null));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey',
            )
        );
        $this->assertTrue($validator->isValid('matchValue'));
    }

    public function testCanValidateIfThereIsTheSameObjectInTheRepository()
    {
        $match = new stdClass();

        $classMetadata = $this->getMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierValues')
            ->with($match)
            ->will($this->returnValue(array('id' => 'identifier')));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');
        $objectManager->expects($this->any())
                      ->method('getClassMetadata')
                      ->with('stdClass')
                      ->will($this->returnValue($classMetadata));

        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('stdClass'));
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('matchKey' => 'matchValue'))
            ->will($this->returnValue($match));

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey'
            )
        );
        $this->assertTrue($validator->isValid(array('matchKey' => 'matchValue', 'id' => 'identifier')));
    }

    public function testCannotValidateIfThereIsAnotherObjectWithTheSameValueInTheRepository()
    {
        $match = new stdClass();

        $classMetadata = $this->getMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierValues')
            ->with($match)
            ->will($this->returnValue(array('id' => 'identifier')));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');
        $objectManager->expects($this->any())
                      ->method('getClassMetadata')
                      ->with('stdClass')
                      ->will($this->returnValue($classMetadata));

        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('stdClass'));
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('matchKey' => 'matchValue'))
            ->will($this->returnValue($match));

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey'
            )
        );
        $this->assertFalse($validator->isValid(array('matchKey' => 'matchValue', 'id' => 'another identifier')));
    }

    public function testCanFetchIdentifierFromContext()
    {
        $match = new stdClass();

        $classMetadata = $this->getMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierValues')
            ->with($match)
            ->will($this->returnValue(array('id' => 'identifier')));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');
        $objectManager->expects($this->any())
                      ->method('getClassMetadata')
                      ->with('stdClass')
                      ->will($this->returnValue($classMetadata));

        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('stdClass'));
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('matchKey' => 'matchValue'))
            ->will($this->returnValue($match));

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey',
                'use_context'       => true
            )
        );
        $this->assertTrue($validator->isValid('matchValue', array('id' => 'identifier')));
    }

    /**
     * @expectedException \Zend\Validator\Exception\RuntimeException
     * @expectedExceptionMessage Expected context to be an array but is null
     */
    public function testThrowsAnExceptionOnUsedButMissingContext()
    {
        $match = new stdClass();

        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('matchKey' => 'matchValue'))
            ->will($this->returnValue($match));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey',
                'use_context'       => true
            )
        );
        $validator->isValid('matchValue');
    }

    /**
     * @expectedException \Zend\Validator\Exception\RuntimeException
     * @expectedExceptionMessage Expected context to contain id
     */
    public function testThrowsAnExceptionOnMissingIdentifier()
    {
        $match = new stdClass();

        $classMetadata = $this->getMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');
        $objectManager->expects($this->any())
                      ->method('getClassMetadata')
                      ->with('stdClass')
                      ->will($this->returnValue($classMetadata));

        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('stdClass'));
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('matchKey' => 'matchValue'))
            ->will($this->returnValue($match));

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey'
            )
        );
        $validator->isValid('matchValue');
    }

    /**
     * @expectedException \Zend\Validator\Exception\RuntimeException
     * @expectedExceptionMessage Expected context to contain id
     */
    public function testThrowsAnExceptionOnMissingIdentifierInContext()
    {
        $match = new stdClass();

        $classMetadata = $this->getMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');
        $objectManager->expects($this->any())
                      ->method('getClassMetadata')
                      ->with('stdClass')
                      ->will($this->returnValue($classMetadata));

        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('stdClass'));
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('matchKey' => 'matchValue'))
            ->will($this->returnValue($match));

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey',
                'use_context'       => true
            )
        );
        $validator->isValid('matchValue', array());
    }

    /**
     * @expectedException \Zend\Validator\Exception\InvalidArgumentException
     * @expectedExceptionMessage Option "object_manager" is required and must be
     *                           an instance of Doctrine\Persistence\ObjectManager, nothing given
     */
    public function testThrowsAnExceptionOnMissingObjectManager()
    {
        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');

        new UniqueObject(
            array(
                'object_repository' => $repository,
                'fields'            => 'matchKey'
            )
        );
    }

    /**
     * @expectedException \Zend\Validator\Exception\InvalidArgumentException
     * @expectedExceptionMessage Option "object_manager" is required and must be
     *                           an instance of Doctrine\Persistence\ObjectManager, nothing given
     */
    public function testThrowsAnExceptionOnWrongObjectManager()
    {
        $objectManager = new stdClass();

        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');

        new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey'
            )
        );
    }

    public function testCanValidateWithNotAvailableObjectInRepositoryByDateTimeObject()
    {
        $date       = new \DateTime("17 March 2014");
        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('date' => $date))
            ->will($this->returnValue(null));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'date',
            )
        );

        $this->assertTrue($validator->isValid($date));
    }

    public function testCanFetchIdentifierFromObjectContext()
    {
        $context     = new stdClass();
        $context->id = 'identifier';

        $match = new stdClass();

        $classMetadata = $this->getMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects($this->at(0))
            ->method('getIdentifierValues')
            ->with($context)
            ->will($this->returnValue(array('id' => 'identifier')));
        $classMetadata
            ->expects($this->at(1))
            ->method('getIdentifierValues')
            ->with($match)
            ->will($this->returnValue(array('id' => 'identifier')));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');
        $objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->with('stdClass')
            ->will($this->returnValue($classMetadata));

        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('stdClass'));
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('matchKey' => 'matchValue'))
            ->will($this->returnValue($match));

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey',
                'use_context'       => true
            )
        );

        $this->assertTrue($validator->isValid('matchValue', $context));
    }
    
    public function testErrorMessageIsStringInsteadArray()
    {
        $match = new stdClass();

        $classMetadata = $this->getMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));
        $classMetadata
            ->expects($this->once())
            ->method('getIdentifierValues')
            ->with($match)
            ->will($this->returnValue(array('id' => 'identifier')));

        $objectManager = $this->getMock('Doctrine\Persistence\ObjectManager');
        $objectManager->expects($this->any())
                      ->method('getClassMetadata')
                      ->with('stdClass')
                      ->will($this->returnValue($classMetadata));

        $repository = $this->getMock('Doctrine\Persistence\ObjectRepository');
        $repository
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('stdClass'));
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('matchKey' => 'matchValue'))
            ->will($this->returnValue($match));

        $validator = new UniqueObject(
            array(
                'object_repository' => $repository,
                'object_manager'    => $objectManager,
                'fields'            => 'matchKey',
                'use_context'       => true
            )
        );
        $this->assertFalse(
            $validator->isValid(
                'matchValue',
                array('matchKey' => 'matchValue', 'id' => 'another identifier')
            )
        );
        $messageTemplates = $validator->getMessageTemplates();
        
        $expectedMessage = str_replace(
            '%value%',
            'matchValue',
            $messageTemplates[UniqueObject::ERROR_OBJECT_NOT_UNIQUE]
        );
        $messages        = $validator->getMessages();
        $receivedMessage = $messages[UniqueObject::ERROR_OBJECT_NOT_UNIQUE];
        $this->assertTrue($expectedMessage == $receivedMessage);
    }
}
