<?php namespace talenthub\Http\Controllers;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use talenthub\Http\Requests;
use talenthub\Http\Controllers\Controller;

use Illuminate\Http\Request;
use talenthub\Http\Requests\TalentCVRequest;
use talenthub\Http\Requests\UserProfileRequest;
use talenthub\ManagerCareerInformation;
use talenthub\ManagerProfile;
use talenthub\Repositories\BasicSiteRepository;
use talenthub\Repositories\SiteConstants;
use talenthub\Repositories\SiteSessions;
use talenthub\Repositories\SportsRepository;
use talenthub\Repositories\StorageLocationsRepository;
use talenthub\Repositories\UserProfileRepository;
use talenthub\TalentCareerInformation;
use talenthub\TalentCareerReferences;
use talenthub\TalentCareerStatisticsModels\BaseBallStatistics;
use talenthub\TalentCareerStatisticsModels\BasketBallStatistics;
use talenthub\TalentCareerStatisticsModels\RugbyStatistics;
use talenthub\TalentCareerStatisticsModels\SoccerStatistics;
use talenthub\TalentCareerStatisticsModels\SwimmingStatistics;
use talenthub\TalentCareerStatisticsModels\TrackAndFieldStatistics;
use talenthub\User;
use talenthub\UserProfile;

class ProfileController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
        Blade::setContentTags('<%', '%>');        // for variables and all things Blade
        Blade::setEscapedContentTags('<%%', '%%>');   // for escaped data

        $userProfile = UserProfile::find(Session::get(SiteSessions::USER_ID));
        //$userProfile->unmuatedDataForPresentation();
        $userProfile->getMutatedData = false;

        $country=BasicSiteRepository::getListOfCountries();
        $sportPositions=array_map('ucfirst',SportsRepository::getSportPositions(Session::get(SiteSessions::USER_SPORT_TYPE)));

		return view('profile.home',compact('userProfile','country','sportPositions'));
	}


    /**
     *Function to upload Profile Image
     */
    public function uploadImage(Request $request)
    {
        if(!$request->hasFile("profile_image"))
        {
            return redirect()->back()->withErrors(["imageUploaded"=>'image not found']);
        }
        $validator = Validator::make($request->all(), ['profile_image'=>'required']);
        $imageFile = $request->file("profile_image");
        $imageExt = $imageFile->getClientOriginalExtension();
        if(array_search(strtolower($imageExt),['jpg','jpeg','png','gif']))
        {
            dd($imageExt);
            return redirect()->back()->withErrors(["imageUploaded"=>'File extension is not supported.']);
        }
        if($validator->fails())
        {
            $errors = $validator->errors();
            dd($errors);
            return redirect()->back()->withErrors($validator->errors());
        }
        $image = Image::make($imageFile)->fit(300,300);

        $icon_image = Image::make($imageFile)->fit(60,60);
        $small_image = Image::make($imageFile)->fit(100,100);

        $this->createImageSourceDirectories();

        $fileName=hash("md5",$imageFile->getClientOriginalName().Session::get(SiteSessions::USER_ID).Session::get(SiteSessions::USER_NAME));
        $image->save(public_path().StorageLocationsRepository::USER_PROFILE_IMAGE_PATH.$fileName);
        $icon_image->save(public_path().StorageLocationsRepository::USER_PROFILE_IMAGE_ICON_PATH.$fileName);
        $small_image->save(public_path().StorageLocationsRepository::USER_PROFILE_IMAGE_SMALL_PATH.$fileName);

        $userProfile = UserProfile::find(Session::get(SiteSessions::USER_ID));
        $userProfile->profile_image_path = url(StorageLocationsRepository::USER_PROFILE_IMAGE_PATH.$fileName);
        $userProfile->save();

        return redirect()->back()->with(["imageUploaded"=>"successfully"]);
    }

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit()
	{
        $user=UserProfile::find(Session::get(SiteSessions::USER_ID));
        $gender=UserProfileRepository::getUserGender();
        $addressType=UserProfileRepository::getAddressTypes();
        $instituteType=UserProfileRepository::getInstituteType();
        $country=BasicSiteRepository::getListOfCountries();
        $livingWith=UserProfileRepository::getLivingWithType();
        $gradeAverage=UserProfileRepository::getGradeAverageType();
        $sports=BasicSiteRepository::getSportTypes();
        if(SiteConstants::isTalent(Session::get(SiteSessions::USER_TYPE)))
        {
            $userProfile=UserProfile::find($user->user_id);
            return view('profile.talent.edit',compact('userProfile','gender','addressType','instituteType','country','livingWith','gradeAverage'));
        }
        else if(SiteConstants::isCoach(Session::get(SiteSessions::USER_TYPE)))
        {
            $managerProfile=ManagerProfile::find($user->user_id);
            $managerCareerInformation = $managerProfile->managerCareerInformation()->get();
            return view('profile.manager.edit',compact('managerProfile','gender','addressType','instituteType','country','livingWith','gradeAverage','sports'));
        }
        else if(Session::get(SiteSessions::USER_ID)=="")
        {
            return redirect('/');
        }
	}


    /**
     * Function to handle Edit CV request for Talent only
     * @param $id
     */
    public function editCV()
    {
        $country=BasicSiteRepository::getListOfCountries();

        $clubDataMap=SportsRepository::getClubDataMap($this->getSportDataMap(Session::get(SiteSessions::USER_SPORT_TYPE)),true);
        $schoolDataMap=SportsRepository::getSchoolDataMap($this->getSportDataMap(Session::get(SiteSessions::USER_SPORT_TYPE)),true);
        $sportPositions=array_map('ucfirst',SportsRepository::getSportPositions(Session::get(SiteSessions::USER_SPORT_TYPE)));
        $clubLeagueLevel = SportsRepository::getClubLeagueLevel();
        $clubLeagueStatus = SportsRepository::getClubLeagueStatus();
        $schoolTeamReputation = SportsRepository::getSchoolTeamReputation();
        $schoolTeamSideLevel = SportsRepository::getSchoolTeamSideLevel();
        $institutionType = UserProfileRepository::getInstituteType();

        if(SiteConstants::isTalent(Session::get(SiteSessions::USER_TYPE)))
        {
            $talentProfile=UserProfile::find(Session::get(SiteSessions::USER_ID));
            $clubCareerInformation = $talentProfile->careerInformation()->where('career_type','=',SiteConstants::CAREER_TYPE_CLUB)->get();
            $schoolCareerInformation = $talentProfile->careerInformation()->where('career_type','=',SiteConstants::CAREER_TYPE_SCHOOL)->get();

            return view('profile.talent.editCV',compact('talentProfile','country','clubDataMap','schoolDataMap','sportPositions',
                'clubLeagueLevel','clubLeagueStatus','schoolTeamReputation','schoolTeamSideLevel','institutionType',
                'clubCareerInformation','schoolCareerInformation'));
        }
    }

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id, UserProfileRequest $request)
	{

        if(SiteConstants::isTalent(Session::get(SiteSessions::USER_TYPE)))
        {
            $user=UserProfile::find($id);
            foreach($request->all() as $key=>$value)
            {
                if(isset($user->$key))
                    $user->$key=$value;
            }

            $user->save();
        }
        else if(SiteConstants::isManager(Session::get(SiteSessions::USER_TYPE)))
        {
            $managerProfile = ManagerProfile::find($id);
            foreach($request->all() as $key=>$value)
            {
                if(isset($managerProfile->$key))
                    $managerProfile->$key=$value;
            }

            $managerProfile->save();

            $managerProfile=$this->storeAndSyncManagerCareerInformation($managerProfile,$request);
            $managerProfile->managerCareerInformation;
        }

        if(Session::has(SiteSessions::USER_COMPLETING_PROFILE_FIRST_TIME))
        {
            return redirect('profile/editCV');
        }
        return redirect('profile/edit');
	}


    /**
     * Updates the CV of a Talent for corresponding sports.
     *
     * @param $id
     */
    public function updateCV(Request $request)
    {
        //--------- Need to work over this function afterwards -------//
        $this->validateCVData($request);
        //--------- Need to work over this function afterwards -------//

        $userProfile = UserProfile::find(Session::get(SiteSessions::USER_ID));
        $userParamsData="";
        if(count($request->positions)>0)
        {
            $userProfile->positions=$request->positions;
            $userProfile->preferred_position = $request->preferred_position;
        }

        $userProfile->params=$this->getParamsDataForProfile($request);

        $userProfile->save();

        //Storing Club and School information and their respective sport statistics
        $this->storeClubSchoolInformation($userProfile,$request);

        if(Session::has(SiteSessions::USER_COMPLETING_PROFILE_FIRST_TIME))
        {
            return redirect('');
        }

        return redirect('profile/editCV');
    }

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


    /**
     *Returning the view after profile is completed
     */
    public function completed()
    {
        return view('profile.talent.profile_completed');
    }


    /**
     * The method is storing Career information of a manager and syncing it with already stored information in the database
     *
     * @param $mangerProfile, $request
     */
    public function storeAndSyncManagerCareerInformation(ManagerProfile $managerProfile, $request)
    {
        //Getting existing career information data
        $existing_career_information = $managerProfile->managerCareerInformation;
        $count = count($request->company);
        $managerCareerInformation = array();
        //Iterating till number of career information is provided by the user
        for ($i = 0; $i < $count; $i++) {
            //If existing information is greater than the current iteration, then assigning existing model to the object
            if($i<count($existing_career_information))
            {
                $managerCareerInformation[$i] = $existing_career_information[$i];
            }
            //If existing information is less than the current iteration, then assigning new model
            else if($i>=count($existing_career_information))
            {
                $managerCareerInformation[$i] = new ManagerCareerInformation();
            }

            $managerCareerInformation[$i]->company = $request->company[$i];
            $managerCareerInformation[$i]->industry = $request->industry[$i];
            $managerCareerInformation[$i]->position_held = $request->position_held[$i];
            $managerCareerInformation[$i]->career_country = $request->career_country[$i];
            $managerCareerInformation[$i]->duties = $request->duties[$i];
            $managerCareerInformation[$i]->from_date = $request->from_date[$i];
            $managerCareerInformation[$i]->to_date = $request->to_date[$i];

            if($i>=count($existing_career_information))
            {
                $managerCareerInformation[$i] = $managerProfile->managerCareerInformation()->create($managerCareerInformation[$i]->toArray());
            }
            $managerCareerInformation[$i]->save();

        }
        if (count($existing_career_information) > $count)
        {
            for($i=$count;$i<count($existing_career_information);$i++)
            {
                $existing_career_information[$i]->delete();
            }
        }
        return $managerProfile;
    }


    /**
     * Storing Club and School Information as received by the $request Object
     * @param $userProfile
     * @param $request
     */
    public function storeClubSchoolInformation($userProfile,$request)
    {
        //Store Club Information
        $this->storeCareer($userProfile,$request, SiteConstants::CAREER_TYPE_CLUB);

        //Store School Information
        $this->storeCareer($userProfile,$request, SiteConstants::CAREER_TYPE_SCHOOL);
    }


    /**
     *Getting the extra parameters of a user profile and modifying it in a form such as [dominant_hand:right|speed_40:50], etc.
     * @param $request
     */
    public function getParamsDataForProfile($request)
    {
        $paramsData = "";
        $userProfileParamsKeys = SportsRepository::getExtraParamsKeysUserProfile();

        foreach($userProfileParamsKeys as $key=>$value)
        {
            if($request->has($value))
            {
                $paramsData.= $value.":".$request->get($value)."|";
            }
        }

        return count($paramsData)>0 ? substr($paramsData,0,-1) : $paramsData;
    }


    /**
     * Store the full information of Career, statistics and references.
     * Also, save career based upon career type
     * @param $request
     * @param $career_type
     */
    public function storeCareer($userProfile,$request,$career_type)
    {
        $sportDataMap = $this->getSportDataMap(Session::get(SiteSessions::USER_SPORT_TYPE));
        $formRequest = $request->all();
        $careerStatisticsType="";
        $careerInformationFillAbles="";
        $careerTypeSportDataMap="";
        $careerCounts="";

        $existingCareer="";
        $existingCareerCount="";

        $fieldPrefix="";

        switch($career_type)
        {
            case SiteConstants::CAREER_TYPE_CLUB:
                //Store Club Information
                $careerInformationFillAbles = TalentCareerInformation::$club_fillable;
                $careerTypeSportDataMap=SportsRepository::getClubDataMap($this->getSportDataMap(Session::get(SiteSessions::USER_SPORT_TYPE)));
                $careerCounts = count($formRequest["club_club_school_name"]);
                $fieldPrefix="club_";
                $existingCareer=$userProfile->careerInformation()->where('career_type','=',SiteConstants::CAREER_TYPE_CLUB)->get();
                $existingCareerCount = count($existingCareer);
                break;

            case SiteConstants::CAREER_TYPE_SCHOOL:
                //Store Club Information
                $careerInformationFillAbles = TalentCareerInformation::$school_fillable;
                $careerTypeSportDataMap=SportsRepository::getSchoolDataMap($this->getSportDataMap(Session::get(SiteSessions::USER_SPORT_TYPE)));
                $careerCounts = count($formRequest["school_club_school_name"]);
                $fieldPrefix="school_";
                $existingCareer=$userProfile->careerInformation()->where('career_type','=',SiteConstants::CAREER_TYPE_SCHOOL)->get();
                $existingCareerCount = count($existingCareer);
                break;

            default:
                return false;
        }
//        var_dump($careerTypeSportDataMap);
//        dd($formRequest);
        /*
         * For loop for storing Career Information
         */
        for($i=0;$i<$careerCounts;$i++)
        {
            $talentCareerInformation="";
            if($i<$existingCareerCount)
            {
                $talentCareerInformation=$existingCareer[$i];
            }
            else if($i>=$existingCareerCount)
            {
                $talentCareerInformation=new TalentCareerInformation();
            }

            $talentCareerInformation->career_type = $career_type;
            foreach($careerInformationFillAbles as $value)
            {
                $requestField=$fieldPrefix.$value;
                $talentCareerInformation->$value = $formRequest[$requestField][$i];
            }

            if($i>=$existingCareerCount)
            {
                $talentCareerInformation=$userProfile->careerInformation()->create($talentCareerInformation->toArray());
            }
            $talentCareerInformation->save();

            /*
             * This code is for storing Sport statistics in the database
             */
            $sportStatisticsCount = count($formRequest[array_values($careerTypeSportDataMap)[0]][$i]);

            $existingSportStatistics = $talentCareerInformation->careerSportStatistics(Session::get(SiteSessions::USER_SPORT_TYPE))->get();

            for($j=0;$j<$sportStatisticsCount;$j++)
            {
                $careerStatistics="";
                if($j<count($existingSportStatistics))
                {
                    $careerStatistics=$existingSportStatistics[$j];
                }
                else if($j>=count($existingSportStatistics))
                {
                    $careerStatistics = $this->getCareerStatisticsModelObject(Session::get(SiteSessions::USER_SPORT_TYPE));
                }

                $careerStatistics->statistic_type=$career_type;
                foreach($careerTypeSportDataMap as $key=>$value)
                {
                    $statisticField = $sportDataMap[$key];
                    $careerStatistics->$statisticField = $formRequest[$careerTypeSportDataMap[$key]][$i][$j];
                }

                if($j>=count($existingSportStatistics))
                {
                    $careerStatistics = $talentCareerInformation->careerSportStatistics(Session::get(SiteSessions::USER_SPORT_TYPE))->create($careerStatistics->toArray());
                }
                $careerStatistics->save();
            }
            if (count($existingSportStatistics) > $sportStatisticsCount)
            {
                for($k=$sportStatisticsCount;$k<count($existingSportStatistics);$k++)
                {
                    $existingSportStatistics[$k]->delete();
                }
            }

            /*
             * This code is for storing references in the database
             */
            $clubReferenceCount = count($formRequest[$fieldPrefix.'name'][$i]);
            $existingReference = $talentCareerInformation->careerReferences()->get();

            for($j=0;$j<$clubReferenceCount;$j++)
            {
                $careerReference ="";
                if($j<count($existingReference))
                {
                    $careerReference = $existingReference[$j];
                }
                else if($j>=count($existingReference))
                {
                    $careerReference = new TalentCareerReferences();
                }
                foreach($careerReference->getFillAbleField() as $value)
                {
                    $inputField = $fieldPrefix.$value;
                    $careerReference->$value = $formRequest[$inputField][$i][$j];
                }

                if($j>=count($existingReference))
                {
                    $careerReference = $talentCareerInformation->careerReferences()->create($careerReference->toArray());
                }
                $careerReference->save();
            }
            if (count($existingReference) > $clubReferenceCount)
            {
                for($k=$clubReferenceCount;$k<count($existingReference);$k++)
                {
                    $existingReference[$k]->delete();
                }
            }
        }

        if ($existingCareerCount > $careerCounts)
        {
            for($i=$careerCounts;$i<$existingCareerCount;$i++)
            {
                $existingCareer[$i]->delete();
            }
        }
        //dd($formRequest);
    }


    /**
     * Retrieve the sport data map, based upon the sports type of the user
     * @param $sportType
     */
    public function getSportDataMap($sportType)
    {
        switch($sportType)
        {
            case SportsRepository::BASEBALL:
                return BaseBallStatistics::$dataMap;
                break;
            case SportsRepository::BASKETBALL:
                return BasketBallStatistics::$dataMap;
                break;
            case SportsRepository::FOOTBALL:
                return BaseBallStatistics::$dataMap;
                break;
            case SportsRepository::RUGBY:
                return RugbyStatistics::$dataMap;
                break;
            case SportsRepository::SOCCER:
                return SoccerStatistics::$dataMap;
                break;
            case SportsRepository::SOFTBALL:
                return BaseBallStatistics::$dataMap;
                break;
            case SportsRepository::SWIMMING:
                return SwimmingStatistics::$dataMap;
                break;
            case SportsRepository::TENNIS:
                return BaseBallStatistics::$dataMap;
                break;
            case SportsRepository::TRACK_FIELD:
                return TrackAndFieldStatistics::$dataMap;
                break;
            case SportsRepository::WATERPOLO:
                return BaseBallStatistics::$dataMap;
                break;

            default:
                return false;
                break;
        }
    }



    /**
     * Creating a new career statistics object based upon the sports type and returning the corresponding model
     * @param $sportType
     */
    public function getCareerStatisticsModelObject($sportType)
    {
        switch($sportType)
        {
            case SportsRepository::BASEBALL:
                return new BaseBallStatistics();
                break;
            case SportsRepository::BASKETBALL:
                return new BaseBallStatistics;
                break;
            case SportsRepository::FOOTBALL:
                return new BaseBallStatistics;
                break;
            case SportsRepository::RUGBY:
                return new BaseBallStatistics;
                break;
            case SportsRepository::SOCCER:
                return new BaseBallStatistics;
                break;
            case SportsRepository::SOFTBALL:
                return new BaseBallStatistics;
                break;
            case SportsRepository::SWIMMING:
                return new BaseBallStatistics;
                break;
            case SportsRepository::TENNIS:
                return new BaseBallStatistics;
                break;
            case SportsRepository::TRACK_FIELD:
                return new BaseBallStatistics;
                break;
            case SportsRepository::WATERPOLO:
                return new BaseBallStatistics;
                break;

            default:
                return false;
                break;
        }
    }


    /**
     * --------- Need to implement this logic ------- Logic is that Validate data, if errors then create a session's flash
     * and store all the user data in it and then make changes on the view to reflect it
     *
     * Validating data for CV
     * @param $request
     */
    public function validateCVData($request)
    {
        $rules=[];
        foreach($request->get("positions") as $key=>$value)
        {
            $rules['positions.'.$key]="in:".implode(",",array_keys(SportsRepository::getSportPositions(Session::get(SiteSessions::USER_SPORT_TYPE))));
        }
        $rules['preferred_position']='in:'.implode(",",array_keys(SportsRepository::getSportPositions(Session::get(SiteSessions::USER_SPORT_TYPE))));

        $validator = Validator::make($request->all(),$rules);

        if($validator->fails())
        {
            //dd($validator->errors());
        }

        //dd($validator);


    }


    /**
     *Check is directory exists or not , if not then creates the directories
     */
    public function createImageSourceDirectories()
    {
        if(!file_exists(public_path().StorageLocationsRepository::USER_PROFILE_IMAGE_PATH))
        {
            $result = File::makeDirectory(public_path().StorageLocationsRepository::USER_PROFILE_IMAGE_PATH, 0775, true);
        }
        if(!file_exists(public_path().StorageLocationsRepository::USER_PROFILE_IMAGE_SMALL_PATH))
        {
            $result = File::makeDirectory(public_path().StorageLocationsRepository::USER_PROFILE_IMAGE_SMALL_PATH, 0775, true);
        }
        if(!file_exists(public_path().StorageLocationsRepository::USER_PROFILE_IMAGE_ICON_PATH))
        {
            $result = File::makeDirectory(public_path().StorageLocationsRepository::USER_PROFILE_IMAGE_ICON_PATH, 0775, true);
        }
    }


}