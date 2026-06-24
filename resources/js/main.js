import RelationsManager from "./Components/RelationsManager.vue";

pkp.registry.registerComponent("RelationsManager", RelationsManager);

const relationTypes = [
    {value: 'hasPreprint', label: 'hasPreprint'},
    {value: 'isTranslationOf', label: 'isTranslationOf'},
    {value: 'hasTranslation', label: 'hasTranslation'},
    {value: 'isReviewOf', label: 'isReviewOf'},
    {value: 'isCommentOn', label: 'isCommentOn'},
    {value: 'hasComment', label: 'hasComment'},
];

const identifierTypes = [
    {value: 'DOI', label: 'DOI'},
    {value: 'ISSN', label: 'ISSN'},
    {value: 'ISBN', label: 'ISBN'},
    {value: 'URI', label: 'URI'},
    {value: 'Handle', label: 'Handle'},
    {value: 'ARXIV', label: 'arXiv'},
    {value: 'PMID', label: 'PMID'},
    {value: 'PMCID', label: 'PMCID'},
    {value: 'UUID', label: 'UUID'},
    {value: 'Other', label: 'Other'},
];

pkp.registry.storeExtend("workflow", (piniaContext) => {
    const workflowStore = piniaContext.store;

    workflowStore.extender.extendFn("getMenuItems", (menuItems) => {
        const {useLocalize} = pkp.modules.useLocalize;
        const {t} = useLocalize();

        return menuItems.map((item) => {
            if (item.key === "publication") {
                return {
                    ...item,
                    items: [
                        ...item.items,
                        {
                            key: "publication_relations",
                            label: t("plugins.generic.relations.relationsData"),
                            state: {
                                primaryMenuItem: "publication",
                                secondaryMenuItem: "relations",
                                title: t("plugins.generic.relations.publication.relationsData"),
                            },
                        },
                    ],
                };
            }
            return item;
        });
    });

    workflowStore.extender.extendFn("getPrimaryItems", (primaryItems, args) => {
        if (
            args?.selectedMenuState?.primaryMenuItem === "publication" &&
            args?.selectedMenuState?.secondaryMenuItem === "relations"
        ) {
            return [
                {
                    component: "RelationsManager",
                    props: {
                        submission: args?.submission,
                        publication: args?.selectedPublication,
                        relationTypes,
                        identifierTypes,
                    },
                },
            ];
        }
        return primaryItems;
    });
});