<?php

namespace OC\AppointmentsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
// Include the used classes as JsonResponse and the Request Object
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

// l'entity of appointment
use OC\AppointmentsBundle\Entity\Appointment;
// l'entity of categories
use OC\AppointmentsBundle\Entity\Categories;

class SchedulerController extends Controller
{
    //methode GET
    public function indexAction()
    {
        // Retrieve entity Manager
        $em = $this->getDoctrine()->getManager();
        
         // Get repository of appointments
        $repositoryAppointments = $em->getRepository('OCAppointmentsBundle:Appointment');
        
        // Get repository of categories
        $repositoryCategories = $em->getRepository('OCAppointmentsBundle:Categories');
        
        // Note that you may want to filter the appointments that you want to send
        // by dates or something, otherwise you will send all the appointments to render
        $appointments = $repositoryAppointments->findAll();
        
        // Generate JSON structure from the appointments to render in the start scheduler.
        $formatedAppointments = $this->formatAppointmentsToJson($appointments);
        
        // Retrieve the data from the repository categories
        $categories = $repositoryCategories->findAll();

        // Generate JSON structure from the data of the repository (in this case the categories)
        // so they can be rendered inside a select on the lightbox
        $formatedCategories = $this->formatCategoriesToJson($categories);

        
        // Render scheduler
        return $this->render("OCAppointmentsBundle:Scheduler:scheduler.html.twig", [
            'appointments' => $formatedAppointments,
            'categories' => $formatedCategories
        ]);
    }
    
    /**
     * Handle the creation of an appointment.
     * methode POST
     */
    Public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repositoryAppointments = $em->getRepository("OCAppointmentsBundle:Appointment");
        
        // Use the same format used by Moment.js in the view
        $format = "d-m-Y H:i:s";
        
        // Create appointment entity and set fields values
        $appointment = new Appointment();

        // Update fields of the appointment
        $appointment->setTitle($request->request->get("title"));
        $appointment->setDescription($request->request->get("description"));
        $appointment->setStartDate(
            \DateTime::createFromFormat($format, $request->request->get("start_date"))
        );
        $appointment->setEndDate(
            \DateTime::createFromFormat($format, $request->request->get("end_date"))
        );
        
        // Don't forget to update the create or update controller with the new field
        $repositoryCategories = $em->getRepository("OCAppointmentsBundle:Categories");
        
        // Search in the repository for a category object with the given ID and
        // set it as value !
         $appointment->setCategory(
              $repositoryCategories->find(
                    $request->request->get("category")
                )
            );
        // Update appointment
        $em->persist($appointment);
        $em->flush();

        return new JsonResponse(array(
            "status" => "success"
        ));
        
    }
    
    /**
     * Handle the update of the appointments.
     * Methode POST
     */
    Public function updateAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repositoryAppointments = $em->getRepository("OCAppointmentsBundle:Appointment");

        $appointmentId = $request->request->get("id");

        $appointment = $repositoryAppointments->find($appointmentId);

        if(!$appointment){
            return new JsonResponse(array(
                "status" => "error",
                "message" => "The appointment to update $appointmentId doesn't exist."
            ));
        }

        // Use the same format used by Moment.js in the view
        $format = "d-m-Y H:i:s";

        // Update fields of the appointment
        $appointment->setTitle($request->request->get("title"));
        $appointment->setDescription($request->request->get("description"));
        $appointment->setStartDate(
            \DateTime::createFromFormat($format, $request->request->get("start_date"))
        );
        $appointment->setEndDate(
            \DateTime::createFromFormat($format, $request->request->get("end_date"))
        );
        
        $repositoryCategories = $em->getRepository("OCAppointmentsBundle:Categories");
        
        $appointment->setCategory(
            $repositoryCategories->find(
                $request->request->get("category")
             )
        );
        
        // Update appointment
        $em->persist($appointment);
        $em->flush();

        return new JsonResponse(array(
            "status" => "success"
        ));
    
    }
    
    /**
     * Deletes an appointment from the database
     * Methode DELET
     */
    Public function deleteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repositoryAppointments = $em->getRepository("OCAppointmentsBundle:Appointment");

        $appointmentId = $request->request->get("id");

        $appointment = $repositoryAppointments->find($appointmentId);

        if(!$appointment){
            return new JsonResponse(array(
                "status" => "error",
                "message" => "The given appointment $appointmentId doesn't exist."
            ));
        }

        // Remove appointment from database !
        $em->remove($appointment);
        $em->flush();       

        return new JsonResponse(array(
            "status" => "success"
        ));
        
        
        
    }
    
    /**
     * Returns a JSON string from a group of appointments that will be rendered on the calendar.
     * You can use a serializer library if you want.
     *
     * The dates need to follow the format d-m-Y H:i e.g : "13-07-2017 09:00"
     *
     *
     * @param $appointments
     */
    private function formatAppointmentsToJson($appointments){
        $formatedAppointments = array();
        
        foreach($appointments as $appointment){
            array_push($formatedAppointments, array(
                "id" => $appointment->getId(),
                "description" => $appointment->getDescription(),
                // Is important to keep the start_date, end_date and text with the same key
                // for the JavaScript area
                // altough the getter could be different e.g:
                // "start_date" => $appointment->getBeginDate();
                "text" => $appointment->getTitle(),
                "start_date" => $appointment->getStartDate()->format("Y-m-d H:i"),
                "end_date" => $appointment->getEndDate()->format("Y-m-d H:i")
            ));
        }

        return json_encode($formatedAppointments);
    }
    
    /**
    * Returns a JSON string from data of a repository. The structure may vary according to the
    * complexity of your forms.
    *
    * @param $categories
    */
    private function formatCategoriesToJson($categories){
    $formatedCategories = array();
    
        foreach($categories as $categorie){
            array_push($formatedCategories, array(
            // Important to set an object with the 2 following properties !
                 "key" => $categorie->getId(),
                "label" => $categorie->getName()
             ));
         }   

         return json_encode($formatedCategories);
    }
}


