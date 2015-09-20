<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Image;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Functional test case.
 */
class NodeDataRepositoryTest extends FunctionalTestCase {

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected $context;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @var WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @var Workspace
	 */
	protected $liveWorkspace;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$this->liveWorkspace = new Workspace('live');
		$this->workspaceRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository');
		$this->workspaceRepository->add($this->liveWorkspace);
		$this->persistenceManager->persistAll();
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$this->context = $this->contextFactory->create(array('workspaceName' => 'live'));
		$this->nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->inject($this->contextFactory, 'contextInstances', array());
	}

	/**
	 * @test
	 */
	public function findNodesByRelatedEntitiesFindsExistingNodeWithMatchingEntityProperty() {
		$rootNode = $this->context->getRootNode();
		$newNode = $rootNode->createNode('test', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithEntities'));

		$testImage = new Image();
		$this->persistenceManager->add($testImage);

		$newNode->setProperty('image', $testImage);

		$this->persistenceManager->persistAll();

		$relationMap = array(
			'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Image' => array($this->persistenceManager->getIdentifierByObject($testImage))
		);

		$result = $this->nodeDataRepository->findNodesByRelatedEntities($relationMap);

		$this->assertCount(1, $result);
	}

	/**
	 * @test
	 */
	public function findNodeByPropertySearch() {
		$this->createNodesForNodeSearchTest();

		$result = $this->nodeDataRepository->findByProperties('simpleTestValue', 'TYPO3.TYPO3CR.Testing:NodeType', $this->liveWorkspace, $this->context->getDimensions());
		$this->assertCount(2, $result);
		$this->assertEquals('test-node-1', array_shift($result)->getName());
		$this->assertEquals('test-node-2', array_shift($result)->getName());
	}

	/**
	 * @test
	 */
	public function findNodesByPropertyKeyAndValue() {
		$this->createNodesForNodeSearchTest();

		$result = $this->nodeDataRepository->findByProperties(array('test2' => 'simpleTestValue'), 'TYPO3.TYPO3CR.Testing:NodeType', $this->liveWorkspace, $this->context->getDimensions());
		$this->assertCount(1, $result);
		$this->assertEquals('test-node-2', array_shift($result)->getName());
	}

	/**
	 * @throws \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
	 */
	protected function createNodesForNodeSearchTest() {
		$rootNode = $this->context->getRootNode();

		$newNode1 = $rootNode->createNode('test-node-1', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeType'));
		$newNode1->setProperty('test1', 'simpleTestValue');

		$newNode2 = $rootNode->createNode('test-node-2', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeType'));
		$newNode2->setProperty('test2', 'simpleTestValue');

		$newNode2 = $rootNode->createNode('test-node-3', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeType'));
		$newNode2->setProperty('test1', 'otherValue');

		$this->persistenceManager->persistAll();
	}
}