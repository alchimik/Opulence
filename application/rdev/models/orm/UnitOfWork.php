<?php
/**
 * Copyright (C) 2014 David Young
 *
 * Defines a unit of work that tracks changes made to entities and atomically persists them
 */
namespace RDev\Models\ORM;
use RDev\Models;
use RDev\Models\Databases\SQL;
use RDev\Models\Exceptions;
use RDev\Models\ORM\DataMappers;

class UnitOfWork
{
    /** @var SQL\RDevPDO The PDO connection to use in our UoW */
    private $pdo = null;
    /** @var array The mapping of class names to their data mappers */
    private $dataMappers = [];
    /** @var array The list of entities scheduled for insertion */
    private $scheduledForInsertion = [];
    /** @var array The list of entities scheduled for update */
    private $scheduledForUpdate = [];
    /** @var array The list of entities scheduled for deletion */
    private $scheduledForDeletion = [];
    /** @var array The mapping of object Ids to their original data */
    private $objectHashIdsToOriginalData = [];
    /** @var array The mapping of entities' object hash Ids to their various states */
    private $entityStates = [];
    /** @var array The mapping of class names to a list of entities of that class */
    private $classNamesToEntities = [];

    /**
     * @param SQL\RDevPDO $pdo The PDO connection to use in our UoW
     */
    public function __construct(SQL\RDevPDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Commits any entities that have been scheduled for insertion/updating/deletion
     */
    public function commit()
    {
        $this->checkForUpdates();
        $this->pdo->beginTransaction();

        try
        {
            $this->insert();
            $this->update();
            $this->delete();
            $this->pdo->commit();
        }
        catch(\Exception $ex)
        {
            Exceptions\Log::write("Failed to commit: " . $ex);
            $this->pdo->rollBack();
        }

        // Clear our schedules
        $this->scheduledForInsertion = [];
        $this->scheduledForUpdate = [];
        $this->scheduledForDeletion = [];
    }

    /**
     * Detaches an entity from being managed
     *
     * @param Models\IEntity $entity The entity to detach
     */
    public function detach(Models\IEntity $entity)
    {
        if($this->isManaged($entity))
        {
            $className = get_class($entity);
            $objectHashId = $this->getObjectHashId($entity);
            unset($this->classNamesToEntities[$className][$entity->getId()]);
            unset($this->objectHashIdsToOriginalData[$objectHashId]);
            unset($this->scheduledForInsertion[$objectHashId]);
            unset($this->scheduledForUpdate[$objectHashId]);
            unset($this->scheduledForDeletion[$objectHashId]);
        }
    }

    /**
     * Disposes of all data in this unit of work
     */
    public function dispose()
    {
        $this->scheduledForInsertion = [];
        $this->scheduledForUpdate = [];
        $this->scheduledForDeletion = [];
        $this->entityStates = [];
        $this->objectHashIdsToOriginalData = [];
    }

    /**
     * Gets the data mapper for the input class
     *
     * @param string $className The name of the class whose data mapper we're searching for
     * @return DataMappers\IDataMapper The data mapper for the input class
     * @throws \RuntimeException Thrown if there was no data mapper for the input class name
     */
    public function getDataMapper($className)
    {
        if(!array_key_exists($className, $this->dataMappers))
        {
            throw new \RuntimeException("No data mapper for " . $className);
        }

        return $this->dataMappers[$className];
    }

    /**
     * Attempts to get a managed entity
     *
     * @param string $className The name of the class the entity belongs to
     * @param int $id The entity's Id
     * @return Models\IEntity|bool The entity if it was found, otherwise false
     */
    public function getManagedEntity($className, $id)
    {
        if(!array_key_exists($className, $this->classNamesToEntities)
            || !array_key_exists($id, $this->classNamesToEntities[$className])
        )
        {
            return false;
        }

        return $this->classNamesToEntities[$className][$id];
    }

    /**
     * Gets whether or not an entity is being managed
     *
     * @param Models\IEntity $entity The entity to check
     * @return bool True if the entity is managed, otherwise false
     */
    public function isManaged(Models\IEntity $entity)
    {
        return $this->entityStates[$this->getObjectHashId($entity)] == EntityStates::MANAGED;
    }

    /**
     * Adds an entity(ies) to manage
     *
     * @param Models\IEntity|array $entities The entity(ies) to manage
     */
    public function manageEntities(Models\IEntity &$entities)
    {
        if(!is_array($entities))
        {
            $entities = [&$entities];
        }

        /** @var Models\IEntity $entity */
        foreach($entities as $entity)
        {
            $className = get_class($entity);
            $objectHashId = $this->getObjectHashId($entity);

            if(!array_key_exists($className, $this->classNamesToEntities))
            {
                $this->classNamesToEntities[$className] = [];
            }

            // Don't double-manage an entity
            if(!array_key_exists($entity->getId(), $this->classNamesToEntities[$className]))
            {
                $this->classNamesToEntities[$className][$entity->getId()] = $entity;
                $this->entityStates[$this->getObjectHashId($entity)] = EntityStates::MANAGED;
                $this->objectHashIdsToOriginalData[$objectHashId] = $entity;
            }
        }
    }

    /**
     * Registers a data mapper for a class
     *
     * @param string $className The name of the class whose data mapper we're registering
     * @param DataMappers\IDataMapper $dataMapper The data mapper for the class
     */
    public function registerDataMapper($className, DataMappers\IDataMapper $dataMapper)
    {
        $this->dataMappers[$className] = $dataMapper;
    }

    /**
     * Schedules an entity for deletion
     *
     * @param Models\IEntity $entity The entity to schedule for deletion
     */
    public function scheduleForDeletion(Models\IEntity $entity)
    {
        $this->scheduledForDeletion[$this->getObjectHashId($entity)] = $entity;
    }

    /**
     * Schedules an entity for insertion
     *
     * @param Models\IEntity $entity The entity to schedule for insertion
     */
    public function scheduleForInsertion(Models\IEntity $entity)
    {
        $this->scheduledForInsertion[$this->getObjectHashId($entity)] = $entity;
    }

    /**
     * Schedules an entity for insertion
     *
     * @param Models\IEntity $entity The entity to schedule for insertion
     */
    public function scheduleForUpdate(Models\IEntity $entity)
    {
        $this->scheduledForUpdate[$this->getObjectHashId($entity)] = $entity;
    }

    /**
     * Checks for any changes made to entities, and if any are found, they're scheduled for update
     */
    private function checkForUpdates()
    {
        foreach($this->classNamesToEntities as $className => $entities)
        {
            /** @var Models\IEntity $entity */
            foreach($entities as $entityId => $entity)
            {
                $objectHashId = $this->getObjectHashId($entity);

                // No point in checking for changes if it's already scheduled for an action
                if($this->isManaged($entity)
                    && !isset($this->scheduledForInsertion[$objectHashId])
                    && !isset($this->scheduledForUpdate[$objectHashId])
                    && !isset($this->scheduledForDeletion[$objectHashId])
                )
                {
                    // Get all the properties in the original entity and the current one
                    $currentEntityReflection = new \ReflectionClass($entity);
                    $currentProperties = $currentEntityReflection->getProperties();
                    $currentPropertiesAsHash = [];
                    $originalEntityReflection = new \ReflectionClass($this->objectHashIdsToOriginalData[$objectHashId]);
                    $originalProperties = $originalEntityReflection->getProperties();
                    $originalPropertiesAsHash = [];

                    // Map each property name to its value for the current entity
                    foreach($currentProperties as $currentProperty)
                    {
                        $currentProperty->setAccessible(true);
                        $currentPropertiesAsHash[$currentProperty->getName()] = $currentProperty->getValue();
                    }

                    // Map each property name to its value for the original entity
                    foreach($originalProperties as $originalProperty)
                    {
                        $originalProperty->setAccessible(true);
                        $originalPropertiesAsHash[$originalProperty->getName()] = $originalProperty->getValue();
                    }

                    if(count(array_diff($currentPropertiesAsHash, $originalPropertiesAsHash)) > 0)
                    {
                        $this->scheduleForUpdate($entity);
                    }
                }
            }
        }
    }

    /**
     * Attempts to update all the entities scheduled for deletion
     */
    private function delete()
    {
        /** @var Models\IEntity $entity */
        foreach($this->scheduledForDeletion as $objectHashId => $entity)
        {
            /** @var DataMappers\IDataMapper $dataMapper */
            $dataMapper = $this->dataMappers[get_class($entity)];
            $dataMapper->delete($entity);
            $this->entityStates[$objectHashId] = EntityStates::DELETED;
        }
    }

    /**
     * Gets a unique hash Id for an object
     *
     * @param mixed $object The object whose hash we want
     * @return string The object hash Id
     */
    private function getObjectHashId($object)
    {
        return spl_object_hash($object);
    }

    /**
     * Attempts to insert all the entities scheduled for insertion
     */
    private function insert()
    {
        /** @var Models\IEntity $entity */
        foreach($this->scheduledForInsertion as $objectHashId => $entity)
        {
            /** @var DataMappers\IDataMapper $dataMapper */
            $dataMapper = $this->dataMappers[get_class($entity)];
            $dataMapper->add($entity);
            $this->manageEntities($entity);
        }
    }

    /**
     * Attempts to update all the entities scheduled for updating
     */
    private function update()
    {
        /** @var Models\IEntity $entity */
        foreach($this->scheduledForUpdate as $objectHashId => $entity)
        {
            /** @var DataMappers\IDataMapper $dataMapper */
            $dataMapper = $this->dataMappers[get_class($entity)];
            $dataMapper->update($entity);
        }
    }
} 