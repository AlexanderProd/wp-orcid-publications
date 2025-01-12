import { registerBlockType } from "@wordpress/blocks";
import { TextControl } from "@wordpress/components";
import { useBlockProps } from "@wordpress/block-editor";
import ServerSideRender from "@wordpress/server-side-render";

registerBlockType("orcid-publications/publications", {
  title: "ORCID Publications",
  icon: "book-alt",
  category: "widgets",
  attributes: {
    orcid: {
      type: "string",
      default: "",
    },
  },
  edit: function (props) {
    const blockProps = useBlockProps();
    const { attributes, setAttributes } = props;

    return (
      <div {...blockProps}>
        <TextControl
          label="ORCID ID"
          value={attributes.orcid}
          onChange={(value) => setAttributes({ orcid: value })}
          help="Enter the ORCID ID (format: 0000-0000-0000-0000)"
        />
        {attributes.orcid && (
          <ServerSideRender
            block="orcid-publications/publications"
            attributes={attributes}
          />
        )}
      </div>
    );
  },
  save: function () {
    return null; // Dynamic block, rendered on PHP side
  },
});
