<?php
namespace Tk\Form\Renderer;


/**
 *
 * NOTE: any css and attributes set in the Layout object is applied to all fields
 * however css and attribute within the LayoutCol object is only applied to the
 * field it belongs to.
 *
 * Example:
 * <code>
 *
 *   $layout = new \Tk\Form\Renderer\Layout();
 *   $layout->setDefaultCol('col-12');
 *   $layout->removeRow('nameFirst', 'col-sm-2');
 *   $layout->removeRow('nameLast', 'col-sm-4');
 *   $layout->removeRow('username', 'col-sm-6');
 *   $layout->removeRow('roleId', 'col');
 *   $layout->removeRow('preferredContact', 'col');
 *   $this->getRenderer()->setLayout($layout);
 *
 * </code>
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class Layout
{
    use \Tk\Dom\AttributesTrait;
    use \Tk\Dom\CssTrait;

    /**
     * @var array|LayoutCol[]
     */
    protected $colList = array();

    /**
     * @var null|LayoutCol
     */
    protected $defaultCol = null;


    /**
     * Reset the layout back to its default state
     * @return Layout
     */
    public function reset()
    {
        $this->colList = array();
        $this->defaultCol = null;
        return $this;
    }

    /**
     * @param string $fieldName
     * @param string $colCss
     * @param string $rowCss
     * @return LayoutCol
     */
    public function addRow($fieldName, $colCss = '', $rowCss = '')
    {
        $col = $this->makeCol($fieldName);
        $col->addCss($colCss);
        $col->setRowEnabled(true);
        return $col;
    }

    /**
     * @param string $fieldName
     * @param string $colCss
     * @param string $rowCss
     * @return LayoutCol
     */
    public function removeRow($fieldName, $colCss = '', $rowCss = '')
    {
        $col = $this->makeCol($fieldName);
        $col->addCss($colCss);
        $col->setRowEnabled(false);
        return $col;
    }

    /**
     * @param string $fieldName
     * @return LayoutCol
     */
    public function makeCol($fieldName)
    {
        if (!$this->hasCol($fieldName)) {
            $this->colList[$fieldName] = new LayoutCol($this, $fieldName);
        }
        return $this->colList[$fieldName];
    }

    /**
     * @param string $fieldName
     * @return LayoutCol|null
     */
    public function getCol($fieldName)
    {
        if ($this->hasCol($fieldName)) {
            return $this->colList[$fieldName];
        }
        return $this->getDefaultCol();
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    public function hasCol($fieldName)
    {
        return isset($this->colList[$fieldName]);
    }

    /**
     * @return null|LayoutCol
     */
    public function getDefaultCol()
    {
        if (!$this->defaultCol) {
            $this->setDefaultCol();
        }
        return $this->defaultCol;
    }

    /**
     * Set this for fields that do not have any col set
     * This is handy for setting default col classes and attributed for fields that have not been set
     *
     * @param string $css
     * @param array $attr
     * @return Layout
     */
    public function setDefaultCol($css = '', $attr = array())
    {
        $this->defaultCol = $this->makeCol('__default_col__');
        $this->defaultCol->addCss($css);
        $this->defaultCol->setAttr($attr);
        $this->defaultCol->setRowEnabled(true);
        return $this;
    }

}