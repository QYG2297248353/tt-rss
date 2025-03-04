<?php
   namespace Controls;

	/**
	 * @param array<string, mixed> $attributes
	 */
   function attributes_to_string(array $attributes): string {
      $rv = [];

      foreach ($attributes as $k => $v) {

         // special handling for "disabled"
         if ($k === "disabled" && !sql_bool_to_bool($v))
            continue;

         array_push($rv, "$k=\"" . htmlspecialchars($v) . "\"");
      }

      return implode(" ", $rv);
   }

   // shortcut syntax (disabled)
   /* function pluginhandler_tags(\Plugin $plugin, string $method) {
      return hidden_tag("op", strtolower(get_class($plugin) . \PluginHost::PUBLIC_METHOD_DELIMITER . $method));
   } */

   function public_method_tags(\Plugin $plugin, string $method): string {
      return hidden_tag("op", strtolower(get_class($plugin) . \PluginHost::PUBLIC_METHOD_DELIMITER . $method));
   }

   function pluginhandler_tags(\Plugin $plugin, string $method): string {
      return hidden_tag("op", "PluginHandler") .
               hidden_tag("plugin", strtolower(get_class($plugin))) .
               hidden_tag("method", $method);
   }

	/**
	 * @param array<string, mixed> $attributes
	 */
   function button_tag(string $value, string $type, array $attributes = []): string {
      return "<button dojoType=\"dijit.form.Button\" ".attributes_to_string($attributes)." type=\"$type\">$value</button>";
   }

	/**
	 * @param array<string, mixed> $attributes
	 */
   function input_tag(string $name, string $value, string $type = "text", array $attributes = [], string $id = ""): string {
      $attributes_str = attributes_to_string($attributes);
      $dojo_type = strpos($attributes_str, "dojoType") === false ? "dojoType='dijit.form.TextBox'" : "";

      return "<input name=\"".htmlspecialchars($name)."\" $dojo_type ".attributes_to_string($attributes)." id=\"".htmlspecialchars($id)."\"
         type=\"$type\" value=\"".htmlspecialchars($value)."\">";
   }

	/**
	 * @param array<string, mixed> $attributes
	 */
   function number_spinner_tag(string $name, string $value, array $attributes = [], string $id = ""): string {
      return input_tag($name, $value, "text", array_merge(["dojoType" => "dijit.form.NumberSpinner"], $attributes), $id);
   }

	/**
	 * @param array<string, mixed> $attributes
	 */
   function submit_tag(string $value, array $attributes = []): string {
      return button_tag($value, "submit", array_merge(["class" => "alt-primary"], $attributes));
   }

	/**
	 * @param array<string, mixed> $attributes
	 */
   function cancel_dialog_tag(string $value, array $attributes = []): string {
      return button_tag($value, "", array_merge(["onclick" => "App.dialogOf(this).hide()"], $attributes));
   }

	/**
	 * @param array<string, mixed> $attributes
	 */
   function icon(string $icon, array $attributes = []): string {
      return "<i class=\"material-icons\" ".attributes_to_string($attributes).">$icon</i>";
   }

	/**
	 * @param mixed $value
	 * @param array<int|string, string> $values
	 * @param array<string, mixed> $attributes
	 */
   function select_tag(string $name, $value, array $values, array $attributes = [], string $id = ""): string {
      $attributes_str = attributes_to_string($attributes);
      $dojo_type = strpos($attributes_str, "dojoType") === false ? "dojoType='fox.form.Select'" : "";

      $rv = "<select $dojo_type name=\"".htmlspecialchars($name)."\"
         id=\"".htmlspecialchars($id)."\" name=\"".htmlspecialchars($name)."\" $attributes_str>";

      foreach ($values as $v) {
         $is_sel = ($v == $value) ? "selected=\"selected\"" : "";

         $rv .= "<option value=\"".htmlspecialchars($v)."\" $is_sel>".htmlspecialchars($v)."</option>";
      }

      $rv .= "</select>";

      return $rv;
   }

   /*function select_labels(string $name, string $value, array $attributes = [], string $id = "") {
      $values = \Labels::get_as_hash($_SESSION["uid"]);

      return select_tag($name, $value, $values, $attributes, $id);
   }*/

	/**
	 * @param mixed $value
	 * @param array<int|string, string> $values
	 * @param array<string, mixed> $attributes
	 */
   function select_hash(string $name, $value, array $values, array $attributes = [], string $id = ""): string {
      $attributes_str = attributes_to_string($attributes);
      $dojo_type = strpos($attributes_str, "dojoType") === false ? "dojoType='fox.form.Select'" : "";

      $rv = "<select $dojo_type name=\"".htmlspecialchars($name)."\"
         id=\"".htmlspecialchars($id)."\" name=\"".htmlspecialchars($name)."\" $attributes_str>";

      foreach ($values as $k => $v) {
         $is_sel = ($k == $value) ? "selected=\"selected\"" : "";

         $rv .= "<option value=\"".htmlspecialchars("$k")."\" $is_sel>".htmlspecialchars($v)."</option>";
      }

      $rv .= "</select>";

      return $rv;
   }

	/**
	 * @param array<string, mixed> $attributes
	 */
   function hidden_tag(string $name, string $value, array $attributes = []): string {
      return "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\"
               ".attributes_to_string($attributes)." name=\"".htmlspecialchars($name)."\"
               value=\"".htmlspecialchars($value)."\">";
   }

	/**
	 * @param array<string, mixed> $attributes
	 */
   function checkbox_tag(string $name, bool $checked = false, string $value = "", array $attributes = [], string $id = ""): string {
      $is_checked = $checked ? "checked" : "";
      $value_str = $value ? "value=\"".htmlspecialchars($value)."\"" : "";

      return "<input dojoType='dijit.form.CheckBox' name=\"".htmlspecialchars($name)."\"
                  $value_str $is_checked ".attributes_to_string($attributes)." id=\"".htmlspecialchars($id)."\">";
   }

	/**
	 * @param array<string, mixed> $attributes
	 */
   function select_feeds_cats(string $name, ?int $default_id = null, array $attributes = [],
                  bool $include_all_cats = true, ?string $root_id = null, int $nest_level = 0, string $id = ""): string {

      $ret = "";

      if (!$root_id) {
         $ret .= "<select name=\"".htmlspecialchars($name)."\"
                        id=\"".htmlspecialchars($id)."\"
                        default=\"".((string)$default_id)."\"
                        dojoType=\"fox.form.Select\" ".attributes_to_string($attributes).">";
      }

      $pdo = \Db::pdo();

      if (!$root_id) $root_id = null;

      $sth = $pdo->prepare("SELECT id,title,
               (SELECT COUNT(id) FROM ttrss_feed_categories AS c2 WHERE
                  c2.parent_cat = ttrss_feed_categories.id) AS num_children
               FROM ttrss_feed_categories
               WHERE owner_uid = :uid AND
               (parent_cat = :root_id OR (:root_id IS NULL AND parent_cat IS NULL)) ORDER BY title");
      $sth->execute([":uid" => $_SESSION['uid'], ":root_id" => $root_id]);

      $found = 0;

      while ($line = $sth->fetch()) {
         ++$found;

         if ($line["id"] == $default_id) {
            $is_selected = "selected=\"1\"";
         } else {
            $is_selected = "";
         }

         for ($i = 0; $i < $nest_level; $i++)
            $line["title"] = " " . $line["title"];

         if ($line["title"])
            $ret .= sprintf("<option $is_selected value='%d'>%s</option>",
               $line["id"], htmlspecialchars($line["title"]));

         if ($line["num_children"] > 0)
            $ret .= select_feeds_cats($id, $default_id, $attributes,
               $include_all_cats, $line["id"], $nest_level+1, $id);
      }

      if (!$root_id) {
         if ($include_all_cats) {
            if ($found > 0) {
               $ret .= "<option disabled=\"1\">―――――――――――――――</option>";
            }

            if ($default_id == 0) {
               $is_selected = "selected=\"1\"";
            } else {
               $is_selected = "";
            }

            $ret .= "<option $is_selected value=\"0\">".__('Uncategorized')."</option>";
         }
         $ret .= "</select>";
      }

      return $ret;
   }
