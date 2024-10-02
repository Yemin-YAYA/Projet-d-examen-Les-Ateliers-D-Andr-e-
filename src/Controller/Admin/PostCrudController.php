<?php

namespace App\Controller\Admin;
use App\Entity\Post;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Post::class;
    }
   public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id')->onlyOnIndex(),
            TextField::new('title','Titre')->setColumns('col-md-6'),     
            AssociationField::new('rubrik', 'Rubrique')->setColumns('col-md-4'),
            TextField::new('content','Texte')->setColumns('col-md-6'),        
            TextField::new('content2','Texte2')->setColumns('col-md-6'),        
            $image= ImageField::new('image1')
                ->setUploadDir('public/divers/images')
                ->setBasePath('divers/images')                
                ->setColumns('col-md-2'),
            $image2= ImageField::new('image2')
                ->setUploadDir('public/divers/images')
                ->setBasePath('divers/images')
                ->setSortable(false)
                ->setFormTypeOption('required',false) 
                ->setColumns('col-md-2'),
            $image3= ImageField::new('image3')
                ->setUploadDir('public/divers/images')
                ->setBasePath('divers/images')
                ->setSortable(false)
                ->setFormTypeOption('required', false) 
                ->setColumns('col-md-2'),
            $image4= ImageField::new('image4')
                ->setUploadDir('public/divers/images')
                ->setBasePath('divers/images')
                ->setSortable(false)
                ->setFormTypeOption('required', false) 
                ->setColumns('col-md-2'),
            AssociationField::new('user', 'Utilisateur')->setColumns('col-md-6'),
            DateField::new('createdAt','Date de création')->onlyOnIndex(),
            BooleanField::new('isPublished')            
            ->setColumns('col-md-1')
            ->setLabel('Publié'),
        ];
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('un article')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(5);
    }
    // Configurer les filtres 
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('user')
            ->add('title')         
            ->add('rubrik')
            ->add('createdAt');
    }
    // Mise en place des actions possibles selon le rôle
    public function configureActions(Actions $actions): Actions
    {
        return $actions
        ->setPermission(Action::DELETE, 'ROLE_ADMIN');
    }
}
