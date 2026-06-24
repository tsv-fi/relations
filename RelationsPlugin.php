<?php

/**
 * @file plugins/generic/relations/RelationsPlugin.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RelationsPlugin
 *
 * @ingroup plugins_generic_relations
 *
 * @brief Inject Google Scholar meta tags into submission views to facilitate indexing.
 */

namespace APP\plugins\generic\relations;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\core\PKPApplication;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\i18n\LocaleConversion;

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
            ['value' => 'PMCID', 'label' => 'PMCID'],
            ['value' => 'UUID', 'label' => 'UUID'],
            ['value' => 'Other', 'label' => 'Other'],
        ];
    }

}
