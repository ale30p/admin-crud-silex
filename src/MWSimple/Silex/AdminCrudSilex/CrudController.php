<?php
namespace MWSimple\Silex\AdminCrudSilex;

use Symfony\Component\HttpFoundation\Request;
use Controller\ConfigController as configController;

class CrudController
{
    protected $app;
    protected $options;
    protected $class;

    public function __construct($app, $options)
    {
        $this->app = $app;
        $this->options = $options;
    }

    public function indexAction()
    {
        $db = $this->app['db'];
        $table = $this->options['table'];
        $sql = "SELECT * FROM $table";
        $entities = $db->fetchAll($sql);

        $list = configController::createList($this->options['table']);

        return $this->app['twig']->render($this->options['dirTemplate'].'index.html.twig', array(
            'entities' => $entities,
            'options' => $this->options,
            'campos' => $list
        ));
    }

    public function newAction()
    {
        $form = configController::createForm($this->options['table'], $this->app);
        // display the form
        return $this->app['twig']->render($this->options['dirTemplate'].'new.html.twig', array(
            'form' => $form->createView(),
            'options' => $this->options
        ));
    }

    public function createAction(Request $request)
    {
        $db = $this->app['db'];
        $table = $this->options['table'];
        $form = configController::createForm($table, $this->app);

        if ('POST' == $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $db->insert($table, $form->getData());
                // return $this->app->redirect($this->app['url_generator']->generate(
                //     $this->options['route'].'_show', array('id' => $entity->getId())
                // ));
                return $this->app->redirect($this->app['url_generator']->generate(
                    $this->options['route']
                ));
            }
        }
        // display the form
        return $this->app['twig']->render($this->options['dirTemplate'].'new.html.twig', array(
            'form' => $form->createView(),
            'options' => $this->options
        ));
    }

    public function showAction($id)
    {
        $db = $this->app['db'];
        $table = $this->options['table'];
        $sql = "SELECT * FROM $table WHERE id = ?";
        $entity = $db->fetchAssoc($sql, array((int) $id));

        if (!$entity) {
            $this->app->abort(404, $table." $id does not exist.");
        }

        $show = configController::createList($this->options['table']);

        $deleteForm = $this->createDeleteForm($id);

        return $this->app['twig']->render($this->options['dirTemplate'].'show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
            'options' => $this->options,
            'campos' => $show
        ));
    }

    public function editAction($id)
    {
        $db = $this->app['db'];
        $table = $this->options['table'];
        $sql = "SELECT * FROM $table WHERE id = ?";
        $entity = $db->fetchAssoc($sql, array((int) $id));

        if (!$entity) {
            $this->app->abort(404, $table." $id does not exist.");
        }

        $editForm = configController::createForm($table, $this->app, $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->app['twig']->render($this->options['dirTemplate'].'edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'options' => $this->options
        ));
    }

    public function updateAction(Request $request, $id)
    {
        $db = $this->app['db'];
        $table = $this->options['table'];
        $sql = "SELECT * FROM $table WHERE id = ?";
        $entity = $db->fetchAssoc($sql, array((int) $id));

        if (!$entity) {
            $this->app->abort(404, $table." $id does not exist.");
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = configController::createForm($table, $this->app, $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $db->update($table, $editForm->getData(), array('id' => $id));

            return $this->app->redirect($this->app['url_generator']->generate(
                $this->options['route'].'_edit', array('id' => $id)
            ));
        }

        return $this->app['twig']->render($this->options['dirTemplate'].'edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'options' => $this->options
        ));
    }

    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $db = $this->app['db'];
            $table = $this->options['table'];
            $sql = "SELECT * FROM $table WHERE id = ?";
            $entity = $db->fetchAssoc($sql, array((int) $id));

            if (!$entity) {
                $this->app->abort(404, $this->options['table']." $id does not exist.");
            }

            $db->delete($table, array('id' => $id));
        }

        return $this->app->redirect($this->app['url_generator']->generate(
            $this->options['route']
        ));
    }

    protected function createDeleteForm($id)
    {
        return $this->app['form.factory']->createBuilder('form', array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}