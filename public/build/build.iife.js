(function(vue) {
  "use strict";
  const _hoisted_1 = { class: "text-4 font-semibold" };
  const _hoisted_2 = ["value", "onChange"];
  const _hoisted_3 = ["value"];
  const _hoisted_4 = ["value", "onChange"];
  const _hoisted_5 = ["value"];
  const _hoisted_6 = ["value", "onInput"];
  const _sfc_main = {
    __name: "RelationsManager",
    props: {
      submission: { type: Object, required: true },
      publication: { type: Object, required: true },
      relationTypes: { type: Array, default: () => [] },
      identifierTypes: { type: Array, default: () => [] }
    },
    setup(__props) {
      const { useLocalize } = pkp.modules.useLocalize;
      const { useUrl } = pkp.modules.useUrl;
      const { useFetch } = pkp.modules.useFetch;
      const { t } = useLocalize();
      const props = __props;
      const relations = vue.ref([]);
      vue.watch(
        () => props.publication,
        (publication) => {
          if (publication) {
            relations.value = publication.relations ?? [];
          }
        },
        { immediate: true }
      );
      const { apiUrl } = useUrl(
        vue.computed(
          () => `submissions/${props.submission.id}/publications/${props.publication.id}`
        )
      );
      const saveBody = vue.ref({ relations: [] });
      const { fetch: putPublication, isLoading: isSaving } = useFetch(apiUrl, {
        method: "PUT",
        body: saveBody
      });
      async function saveRelations() {
        saveBody.value = { relations: JSON.parse(JSON.stringify(relations.value)) };
        await putPublication();
      }
      function addRelation() {
        var _a, _b;
        relations.value.push({
          relationType: ((_a = props.relationTypes[0]) == null ? void 0 : _a.value) ?? "",
          identifierType: ((_b = props.identifierTypes[0]) == null ? void 0 : _b.value) ?? "",
          value: ""
        });
      }
      function removeRelation(index) {
        relations.value.splice(index, 1);
      }
      function updateRelation(index, field, value) {
        relations.value[index] = { ...relations.value[index], [field]: value };
      }
      return (_ctx, _cache) => {
        const _component_PkpTableColumn = vue.resolveComponent("PkpTableColumn");
        const _component_PkpTableHeader = vue.resolveComponent("PkpTableHeader");
        const _component_PkpTableCell = vue.resolveComponent("PkpTableCell");
        const _component_PkpButton = vue.resolveComponent("PkpButton");
        const _component_PkpTableRow = vue.resolveComponent("PkpTableRow");
        const _component_PkpTableBody = vue.resolveComponent("PkpTableBody");
        const _component_PkpTable = vue.resolveComponent("PkpTable");
        return vue.openBlock(), vue.createBlock(_component_PkpTable, null, {
          label: vue.withCtx(() => [
            vue.createElementVNode("h3", _hoisted_1, vue.toDisplayString(vue.unref(t)("plugins.generic.relations.relations")), 1)
          ]),
          "bottom-controls": vue.withCtx(() => [
            vue.createVNode(_component_PkpButton, { onClick: addRelation }, {
              default: vue.withCtx(() => [
                vue.createTextVNode(vue.toDisplayString(vue.unref(t)("plugins.generic.relations.button.addRelation")), 1)
              ]),
              _: 1
            }),
            vue.createVNode(_component_PkpButton, {
              "is-disabled": vue.unref(isSaving),
              onClick: saveRelations
            }, {
              default: vue.withCtx(() => [
                vue.createTextVNode(vue.toDisplayString(vue.unref(isSaving) ? vue.unref(t)("plugins.generic.relations.button.saving") : vue.unref(t)("plugins.generic.relations.button.save")), 1)
              ]),
              _: 1
            }, 8, ["is-disabled"])
          ]),
          default: vue.withCtx(() => [
            vue.createVNode(_component_PkpTableHeader, null, {
              default: vue.withCtx(() => [
                vue.createVNode(_component_PkpTableColumn, null, {
                  default: vue.withCtx(() => [
                    vue.createTextVNode(vue.toDisplayString(vue.unref(t)("plugins.generic.relations.column.relationType")), 1)
                  ]),
                  _: 1
                }),
                vue.createVNode(_component_PkpTableColumn, null, {
                  default: vue.withCtx(() => [
                    vue.createTextVNode(vue.toDisplayString(vue.unref(t)("plugins.generic.relations.column.identifierType")), 1)
                  ]),
                  _: 1
                }),
                vue.createVNode(_component_PkpTableColumn, null, {
                  default: vue.withCtx(() => [
                    vue.createTextVNode(vue.toDisplayString(vue.unref(t)("plugins.generic.relations.column.value")), 1)
                  ]),
                  _: 1
                }),
                vue.createVNode(_component_PkpTableColumn, { class: "w-[100px]" }, {
                  default: vue.withCtx(() => [..._cache[0] || (_cache[0] = [
                    vue.createTextVNode(" ", -1)
                  ])]),
                  _: 1
                })
              ]),
              _: 1
            }),
            vue.createVNode(_component_PkpTableBody, null, {
              default: vue.withCtx(() => [
                (vue.openBlock(true), vue.createElementBlock(vue.Fragment, null, vue.renderList(relations.value, (relation, index) => {
                  return vue.openBlock(), vue.createBlock(_component_PkpTableRow, { key: index }, {
                    default: vue.withCtx(() => [
                      vue.createVNode(_component_PkpTableCell, null, {
                        default: vue.withCtx(() => [
                          vue.createElementVNode("select", {
                            value: relation.relationType,
                            class: "pkpFormField__input pkpFormField--select__input pkpFormField--select__input--sizelarge",
                            onChange: (e) => updateRelation(index, "relationType", e.target.value)
                          }, [
                            (vue.openBlock(true), vue.createElementBlock(vue.Fragment, null, vue.renderList(props.relationTypes, (option) => {
                              return vue.openBlock(), vue.createElementBlock("option", {
                                key: option.value,
                                value: option.value
                              }, vue.toDisplayString(option.label), 9, _hoisted_3);
                            }), 128))
                          ], 40, _hoisted_2)
                        ]),
                        _: 2
                      }, 1024),
                      vue.createVNode(_component_PkpTableCell, null, {
                        default: vue.withCtx(() => [
                          vue.createElementVNode("select", {
                            value: relation.identifierType,
                            class: "pkpFormField__input pkpFormField--select__input pkpFormField--select__input--sizelarge",
                            onChange: (e) => updateRelation(index, "identifierType", e.target.value)
                          }, [
                            (vue.openBlock(true), vue.createElementBlock(vue.Fragment, null, vue.renderList(props.identifierTypes, (option) => {
                              return vue.openBlock(), vue.createElementBlock("option", {
                                key: option.value,
                                value: option.value
                              }, vue.toDisplayString(option.label), 9, _hoisted_5);
                            }), 128))
                          ], 40, _hoisted_4)
                        ]),
                        _: 2
                      }, 1024),
                      vue.createVNode(_component_PkpTableCell, null, {
                        default: vue.withCtx(() => [
                          vue.createElementVNode("input", {
                            value: relation.value,
                            type: "text",
                            class: "pkpFormField__input w-full",
                            onInput: (e) => updateRelation(index, "value", e.target.value)
                          }, null, 40, _hoisted_6)
                        ]),
                        _: 2
                      }, 1024),
                      vue.createVNode(_component_PkpTableCell, null, {
                        default: vue.withCtx(() => [
                          vue.createVNode(_component_PkpButton, {
                            "is-warnable": true,
                            onClick: ($event) => removeRelation(index)
                          }, {
                            default: vue.withCtx(() => [
                              vue.createTextVNode(vue.toDisplayString(vue.unref(t)("plugins.generic.relations.button.remove")), 1)
                            ]),
                            _: 1
                          }, 8, ["onClick"])
                        ]),
                        _: 2
                      }, 1024)
                    ]),
                    _: 2
                  }, 1024);
                }), 128))
              ]),
              _: 1
            })
          ]),
          _: 1
        });
      };
    }
  };
  pkp.registry.registerComponent("RelationsManager", _sfc_main);
  const relationTypes = [
    { value: "hasPreprint", label: "hasPreprint" },
    { value: "isTranslationOf", label: "isTranslationOf" },
    { value: "hasTranslation", label: "hasTranslation" },
    { value: "isReviewOf", label: "isReviewOf" },
    { value: "isCommentOn", label: "isCommentOn" },
    { value: "hasComment", label: "hasComment" }
  ];
  const identifierTypes = [
    { value: "DOI", label: "DOI" },
    { value: "ISSN", label: "ISSN" },
    { value: "ISBN", label: "ISBN" },
    { value: "URI", label: "URI" },
    { value: "Handle", label: "Handle" },
    { value: "ARXIV", label: "arXiv" },
    { value: "PMID", label: "PMID" },
    { value: "PMCID", label: "PMCID" },
    { value: "UUID", label: "UUID" },
    { value: "Other", label: "Other" }
  ];
  pkp.registry.storeExtend("workflow", (piniaContext) => {
    const workflowStore = piniaContext.store;
    workflowStore.extender.extendFn("getMenuItems", (menuItems) => {
      const { useLocalize } = pkp.modules.useLocalize;
      const { t } = useLocalize();
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
                  title: t("plugins.generic.relations.publication.relationsData")
                }
              }
            ]
          };
        }
        return item;
      });
    });
    workflowStore.extender.extendFn("getPrimaryItems", (primaryItems, args) => {
      var _a, _b;
      if (((_a = args == null ? void 0 : args.selectedMenuState) == null ? void 0 : _a.primaryMenuItem) === "publication" && ((_b = args == null ? void 0 : args.selectedMenuState) == null ? void 0 : _b.secondaryMenuItem) === "relations") {
        return [
          {
            component: "RelationsManager",
            props: {
              submission: args == null ? void 0 : args.submission,
              publication: args == null ? void 0 : args.selectedPublication,
              relationTypes,
              identifierTypes
            }
          }
        ];
      }
      return primaryItems;
    });
  });
})(pkp.modules.vue);
