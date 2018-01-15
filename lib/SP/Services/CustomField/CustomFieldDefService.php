<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Services\CustomField;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldDefData;
use SP\DataModel\ItemSearchData;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class CustomFieldDefService
 *
 * @package SP\Services\CustomField
 */
class CustomFieldDefService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

    /**
     * @param $id
     * @return mixed
     */
    public static function getFieldModuleById($id)
    {
        $modules = self::getFieldModules();

        return isset($modules[$id]) ? $modules[$id] : $id;
    }

    /**
     * Devuelve los módulos disponibles para los campos personalizados
     *
     * @return array
     */
    public static function getFieldModules()
    {
        $modules = [
            ActionsInterface::ACCOUNT => __('Cuentas'),
            ActionsInterface::CATEGORY => __('Categorías'),
            ActionsInterface::CLIENT => __('Clientes'),
            ActionsInterface::USER => __('Usuarios'),
            ActionsInterface::GROUP => __('Grupos')

        ];

        return $modules;
    }

    /**
     * Creates an item
     *
     * @param CustomFieldDefData $itemData
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO customFieldsDef SET name = ?, moduleId = ?, required = ?, help = ?, showInList = ?, typeId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getModuleId());
        $Data->addParam($itemData->getRequired());
        $Data->addParam($itemData->getHelp());
        $Data->addParam($itemData->getShowInList());
        $Data->addParam($itemData->getTypeId());
        $Data->setOnErrorMessage(__u('Error al crear el campo personalizado'));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Updates an item
     *
     * @param CustomFieldDefData $itemData
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $query = /** @lang SQL */
            'UPDATE customFieldsDef 
              SET name = ?, moduleId = ?, required = ?, help = ?, showInList = ?, typeId = ?
              WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getModuleId());
        $Data->addParam($itemData->getRequired());
        $Data->addParam($itemData->getHelp());
        $Data->addParam($itemData->getShowInList());
        $Data->addParam($itemData->getTypeId());
        $Data->addParam($itemData->getId());
        $Data->setOnErrorMessage(__u('Error al actualizar el campo personalizado'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return CustomFieldDefData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id, name, moduleId, required, help, showInList, typeId
              FROM customFieldsDef
              WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(CustomFieldDefData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return CustomFieldDefData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id, name, moduleId, required, help, showInList
              FROM customFieldsDef
              ORDER BY moduleId';

        $Data = new QueryData();
        $Data->setMapClassName(CustomFieldDefData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT id, name, moduleId, required, help, showInList, typeId
              FROM customFieldsDef
              WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(CustomFieldDefData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        foreach ($ids as $id) {
            $this->delete($id);
        }
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if ($this->deleteItemsDataForDefinition($id) === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al eliminar el campo personalizado'));
        }

        $query = /** @lang SQL */
            'DELETE FROM customFieldsDef WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar el campo personalizado'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Eliminar los datos de los elementos de una definición
     *
     * @param $id
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function deleteItemsDataForDefinition($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM customFieldsData WHERE id = ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return CustomFieldDefData[]
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setMapClassName(CustomFieldDefData::class);
        $Data->setSelect('a.id, a.name, a.moduleId, a.required, a.help, a.showInList, a.typeId, b.name AS typeName');
        $Data->setFrom('customFieldsDef a INNER JOIN customFieldsType b ON b.id  = a.typeId');
        $Data->setOrder('moduleId');

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        /** @var CustomFieldDefData[] $queryRes */
        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}