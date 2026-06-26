import RelationsManager from "./Components/RelationsManager.vue";

pkp.registry.registerComponent("RelationsManager", RelationsManager);

const relationTypes = [
    {value: 'hasPreprint', label: 'hasPreprint'},
    {value: 'isTranslationOf', label: 'isTranslationOf'},
    {value: 'hasTranslation', label: 'hasTranslation'},
    {value: 'isReviewOf', label: 'isReviewOf'},
    {value: 'hasReview', label: 'hasReview'},
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
                    items: item.items.map((versionItem) => {
                        if (!versionItem.key?.startsWith("publication_") || !versionItem.items) {
                            return versionItem;
                        }
                        const publicationId = versionItem.key.replace("publication_", "");
                        return {
                            ...versionItem,
                            items: [
                                ...versionItem.items,
                                {
                                    key: `publication_${publicationId}_relations`,
                                    label: t("plugins.generic.relations.relationsData"),
                                    state: {
                                        publicationId: parseInt(publicationId),
                                        primaryMenuItem: "publication",
                                        secondaryMenuItem: "relations",
                                        title: t("plugins.generic.relations.publication.relationsData"),
                                    },
                                },
                            ],
                        };
                    }),
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