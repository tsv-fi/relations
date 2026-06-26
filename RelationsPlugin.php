<?php

/**
 * @file plugins/generic/relations/RelationsPlugin.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RelationsPlugin
 *
 * @ingroup plugins_generic_relations
 *
 * @brief This plugin adds a "Relations" field to the publication metadata form, allowing users to specify relationships between publications (e.g. "is translation of", "has preprint", etc.). The relations are stored in the database and can be exported to Crossref and DataCite XML.
 */

namespace APP\plugins\generic\relations;

use APP\core\Application;
use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\core\PKPApplication;
use PKP\dataCitation\pid\PidResolver;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\oai\OAIRecord;

class RelationsPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {


                Hook::add('Schema::get::publication', $this->addRelationsToPublicationSchema(...));
                Hook::add('Publication::validate', $this->validateRelations(...));

                Hook::add('articlecrossrefxmlfilter::execute', $this->addCrossrefElement(...));
                Hook::add('datacitexmlfilter::execute', $this->addDataCiteElement(...));
                Hook::add('JatsTemplatePlugin::jats', $this->addJatsElement(...));

                Hook::add('Templates::Article::Main', $this->displayRelations(...));

                // Registering build file for JS to be loaded
                $request = Application::get()->getRequest();
                $templateMgr = TemplateManager::getManager($request);
                $this->addJavaScript($request, $templateMgr);

            }
            return true;
        }
        return false;
    }

    /**
     * Get the display name of this plugin
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.relations.name');
    }

    /**
     * Get the description of this plugin
     *
     * @return string
     */
    public function getDescription(): string
    {
        return __('plugins.generic.relations.description');
    }

    /**
     * Add a JavaScript file to the backend interface.
     *
     * @param \PKP\core\Request $request The current request
     * @param TemplateManager $templateMgr Template manager instance
     *
     * @return void
     */
    public function addJavaScript($request, $templateMgr): void
    {
        $templateMgr->addJavaScript(
            'relations',
            "{$request->getBaseUrl()}/{$this->getPluginPath()}/public/build/build.iife.js",
            [
                'inline' => false,
                'contexts' => ['backend'],
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST
            ]
        );
    }

    /**
     * Add relations to the publication schema.
     *
     * @param string $hookName `Schema::get::publication`
     * @param array $params
     *
     * @return bool
     */
    public function addRelationsToPublicationSchema($hookName, $params): bool
    {
        $schema = &$params[0];

        $schema->properties->{'relations'} = (object) [
            'type' => 'array',
            'items' => (object) [
                'type' => 'object',
                'properties' => (object) [
                    'relationType' => (object) ['type' => 'string'],
                    'identifierType' => (object) ['type' => 'string'],
                    'value' => (object) ['type' => 'string'],
                ],
            ],
            'apiSummary' => false,
            'validation' => ['nullable'],
        ];

        return false;
    }

    /**
     * Validate the relations field on publication save.
     *
     * @hook Publication::validate [[&$errors, $publication, $props, $allowedLocales, $primaryLocale]]
     */
    public function validateRelations(string $hookName, array $params): bool
    {
        $errors = &$params[0];
        $props  = $params[2];

        if (!isset($props['relations'])) {
            return false;
        }

        foreach ($props['relations'] as $index => $relation) {
            $identifierType = $relation['identifierType'] ?? null;
            $value          = $relation['value'] ?? null;

            if (!$identifierType || !$value) {
                continue;
            }

            $pidClass = PidResolver::resolveByIdentifierType($identifierType);
            if (!$pidClass) {
                continue;
            }

            $parsed = $pidClass::extractFromString($value) ?: $pidClass::removePrefix($value);

            if (!$pidClass::isValid($parsed)) {
                $errors["relations.{$index}.value"][] = __('plugins.generic.relations.validation.invalidValue', [
                    'type'  => $identifierType,
                    'value' => $value,
                ]);
            }
        }

        return false;
    }

    /**
     * Get the relation types supported by this plugin.
     *
     * @return array
     */
    protected function getRelationTypes(): array {
        return [
            ['value' => 'hasPreprint', 'label' => 'hasPreprint'],
            ['value' => 'isTranslationOf', 'label' => 'isTranslationOf'],
            ['value' => 'hasTranslation', 'label' => 'hasTranslation'],
            ['value' => 'isReviewOf', 'label' => 'isReviewOf'],
            ['value' => 'hasReview', 'label' => 'hasReview'],
            ['value' => 'isCommentOn', 'label' => 'isCommentOn'],
            ['value' => 'hasComment', 'label' => 'hasComment'],
        ];
    }

    /**
     * Get the identifier types supported by this plugin.
     *
     * @return array
     */
    protected function getIdentifierTypes(): array {
        return [
            ['value' => 'DOI', 'label' => 'DOI'],
            ['value' => 'ISSN', 'label' => 'ISSN'],
            ['value' => 'ISBN', 'label' => 'ISBN'],
            ['value' => 'URI', 'label' => 'URI'],
            ['value' => 'Handle', 'label' => 'Handle'],
            ['value' => 'ARXIV', 'label' => 'arXiv'],
            ['value' => 'PMID', 'label' => 'PMID'],
        ];
    }

    /**
     * Map our identifierType values to Crossref identifier-type attribute values.
     * 
     * @param string $identifierType The identifier type to map
     * 
     * @return string
     */
    protected function getCrossrefIdentifierType(string $identifierType): string
    {
        return match (strtoupper($identifierType)) {
            'DOI'    => 'doi',
            'ISSN'   => 'issn',
            'ISBN'   => 'isbn',
            'URI'    => 'uri',
            'HANDLE' => 'handle',
            'ARXIV'  => 'arxiv',
            'PMID'   => 'pmid',
            default  => throw new \UnexpectedValueException("Unsupported identifier type: {$identifierType}"),
        };
    }

    /**
     * Map our identifierType values to DataCite relatedIdentifierType attribute values.
     * 
     * @param string $identifierType The identifier type to map
     * 
     * @return string
     */
    protected function getDataCiteIdentifierType(string $identifierType): string
    {
        return match (strtoupper($identifierType)) {
            'DOI'    => 'DOI',
            'ISSN'   => 'ISSN',
            'ISBN'   => 'ISBN',
            'URI'    => 'URL',
            'HANDLE' => 'Handle',
            'ARXIV'  => 'arXiv',
            'PMID'   => 'PMID',
            default  => throw new \UnexpectedValueException("Unsupported identifier type: {$identifierType}"),
        };
    }

    /**
     * Map our relationType values to DataCite relationType attribute values.
     * 
     * @param string $relationType The relation type to map
     * 
     * @return string
     */
    protected function getDataCiteRelationType(string $relationType): string
    {
        return match ($relationType) {
            'hasPreprint'     => 'HasPreprint',
            'isTranslationOf' => 'IsVariantFormOf',
            'hasTranslation'  => 'HasVariantForm',
            'isReviewOf'      => 'Reviews',
            'isCommentOn'     => 'References',
            'hasComment'      => 'IsReferencedBy',
            default           => 'References',
        };
    }

    /**
     * Add <rel:program> relations block to Crossref XML export.
     *
     * @hook articlecrossrefxmlfilter::execute [[&$preliminaryOutput]]
     * 
     * @param string $hookName The name of the hook
     * @param array $params The parameters passed to the hook
     * 
     * @return bool
     */
    public function addCrossrefElement(string $hookName, array $params): bool
    {
        $preliminaryOutput = &$params[0];
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        if (!$context) {
            return false;
        }

        $relationsNS = 'http://www.crossref.org/relations.xsd';
        $rootNode = $preliminaryOutput->documentElement;
        $rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:rel', $relationsNS);

        $articleNodes = $preliminaryOutput->getElementsByTagName('journal_article');
        foreach ($articleNodes as $articleNode) {
            $doiDataNode = $articleNode->getElementsByTagName('doi_data')->item(0);
            $doiNode = $doiDataNode->getElementsByTagName('doi')->item(0);
            $doi = $doiNode->nodeValue;

            $submission = Repo::submission()->getByDoi($doi, $context->getId());
            if (!$submission) {
                continue;
            }

            $publication = $submission->getCurrentPublication();
            $relations = $publication->getData('relations') ?? [];
            if (empty($relations)) {
                continue;
            }

            $programNode = $preliminaryOutput->createElementNS($relationsNS, 'rel:program');
            $programNode->setAttribute('name', 'relations');

            foreach ($relations as $relation) {
                $relationType   = $relation['relationType'] ?? null;
                $identifierType = $relation['identifierType'] ?? null;
                $value          = $relation['value'] ?? null;

                if (!$relationType || !$identifierType || !$value) {
                    continue;
                }

                $relatedItemNode = $preliminaryOutput->createElementNS($relationsNS, 'rel:related_item');
                $elementName = $this->getCrossrefRelationElement($relationType);
                $relationNode = $preliminaryOutput->createElementNS(
                    $relationsNS,
                    $elementName,
                    htmlspecialchars($value, ENT_COMPAT, 'UTF-8')
                );
                $relationNode->setAttribute('relationship-type', $relationType);
                $relationNode->setAttribute('identifier-type', $this->getCrossrefIdentifierType($identifierType));
                $relatedItemNode->appendChild($relationNode);
                $programNode->appendChild($relatedItemNode);
            }

            if ($programNode->hasChildNodes()) {
                $articleNode->insertBefore($programNode, $doiDataNode);
            }
        }

        return false;
    }    

    /**
     * Get the appropriate Crossref relation element based on the relation type.
     *
     * @param string $relationType The relation type
     *
     * @return string The corresponding Crossref relation element
     */
    protected function getCrossrefRelationElement(string $relationType): string
    {
        return match ($relationType) {
            'hasPreprint',
            'isTranslationOf',
            'hasTranslation' => 'rel:intra_work_relation',
            default          => 'rel:inter_work_relation',
        };
    }

    /**
     * Add <relatedIdentifiers> block to DataCite XML export.
     *
     * @hook datacitexmlfilter::execute [[&$preliminaryOutput]]
     * 
     * @param string $hookName The name of the hook
     * @param array $params The parameters passed to the hook
     * 
     * @return bool
     */
    public function addDataCiteElement(string $hookName, array $params): bool
    {
        $preliminaryOutput = &$params[0];
        $dataciteNS = 'http://datacite.org/schema/kernel-4';
        $rootNode = $preliminaryOutput->documentElement;

        $alternateIdentifierNodes = $preliminaryOutput->getElementsByTagName('alternateIdentifier');
        foreach ($alternateIdentifierNodes as $alternateIdentifierNode) {
            if ($alternateIdentifierNode->getAttribute('alternateIdentifierType') !== 'publisherId') {
                continue;
            }

            $idsArray = explode('-', $alternateIdentifierNode->nodeValue);
            if (count($idsArray) !== 3) {
                continue;
            }

            $submissionId = (int) $idsArray[2];
            $submission = Repo::submission()->get($submissionId);
            if (!$submission) {
                continue;
            }

            $publication = $submission->getCurrentPublication();
            $relations = $publication->getData('relations') ?? [];
            if (empty($relations)) {
                continue;
            }

            $relatedIdentifiersNode = $preliminaryOutput->createElementNS($dataciteNS, 'relatedIdentifiers');

            foreach ($relations as $relation) {
                $relationType   = $relation['relationType'] ?? null;
                $identifierType = $relation['identifierType'] ?? null;
                $value          = $relation['value'] ?? null;

                if (!$relationType || !$identifierType || !$value) {
                    continue;
                }

                $relatedIdentifierNode = $preliminaryOutput->createElementNS(
                    $dataciteNS,
                    'relatedIdentifier',
                    htmlspecialchars($value, ENT_COMPAT, 'UTF-8')
                );
                $relatedIdentifierNode->setAttribute(
                    'relatedIdentifierType',
                    $this->getDataCiteIdentifierType($identifierType)
                );
                $relatedIdentifierNode->setAttribute(
                    'relationType',
                    $this->getDataCiteRelationType($relationType)
                );
                $relatedIdentifiersNode->appendChild($relatedIdentifierNode);
            }

            if ($relatedIdentifiersNode->hasChildNodes()) {
                $rootNode->appendChild($relatedIdentifiersNode);
            }
        }

        return false;
    }

    /**
     * Add <related-article> elements to JATS XML export.
     *
     * @hook JatsTemplatePlugin::jats [[&$doc]]
     * 
     * @param string $hookName The name of the hook
     * @param OAIRecord $record The OAI record being exported
     * @param \DOMDocument $doc The DOMDocument representing the JATS XML
     * 
     * @return bool
     */
    public function addJatsElement(string $hookName, OAIRecord $record, \DOMDocument $doc): bool
    {
        $submission = $record->getData('article');
        $publication = $submission->getCurrentPublication();
        $relations = $publication->getData('relations') ?? [];

        if (empty($relations)) {
            return Hook::CONTINUE;
        }

        $xpath = new \DOMXPath($doc);
        $articleMetaNodes = $xpath->query('//article/front/article-meta');
        if (!$articleMetaNodes->length) {
            return Hook::CONTINUE;
        }
        $articleMeta = $articleMetaNodes->item(0);

        foreach ($relations as $relation) {
            $relationType   = $relation['relationType'] ?? null;
            $identifierType = $relation['identifierType'] ?? null;
            $value          = $relation['value'] ?? null;

            if (!$relationType || !$identifierType || !$value) {
                continue;
            }

            $relatedArticleNode = $doc->createElement('related-article');
            $relatedArticleNode->setAttribute('related-article-type', $relationType);
            $relatedArticleNode->setAttribute('ext-link-type', strtolower($identifierType));
            $relatedArticleNode->setAttributeNS(
                'http://www.w3.org/1999/xlink',
                'xlink:href',
                $value
            );
            $articleMeta->appendChild($relatedArticleNode);
        }

        return Hook::CONTINUE;
    }

    /**
     * Display the relations on the publication view page.
     *
     * @hook Templates::Article::Main [[&$smarty, &$output]]
     * 
     * @param string $hookName The name of the hook
     * @param array $params The parameters passed to the hook
     * 
     * @return bool
     */
    public function displayRelations(string $hookName, array $params): bool
    {
        $smarty = &$params[1];
        $output = &$params[2];

        $submission = $smarty->getTemplateVars('article');
        if (!$submission) {
            return Hook::CONTINUE;
        }

        $publication = $submission->getCurrentPublication();
        $relations = $publication->getData('relations') ?? [];

        if (empty($relations)) {
            return Hook::CONTINUE;
        }

        $smarty->assign('relations', $relations);
        $output .= $smarty->fetch($this->getTemplateResource('relationsList.tpl'));

        return Hook::CONTINUE;
    }

}
