<?php

namespace App\Http\Controllers;

use App\Models\Khach_hang;
use App\Models\Nguoi_dung;
use App\Models\Gio_hang;
use App\Models\Lich_su_mua_hang;
use Illuminate\Http\Request;
use App\Models\Bau_vat;
use App\Models\Vat_pham;
use App\Models\Trang_phuc;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\ElseIf_;

class Khach_hang_controller extends Controller
{
    public function view_dang_ky()
    {
        $data=[];
        $data['bao_loi'] = session('bao_loi');
        session()->put('bao_loi', '');
        return view('admin.Dang_ky.dang_ky',$data);
    }
    public function xu_ly_dang_ky(Request $request)
	{
		session()->put('bao_loi', '');
        $dup = Nguoi_dung::find($request->tai_khoan);
        if($dup==null){
            if($request->mat_khau==$request->xac_nhan_mat_khau){
                $gio_hang = new Gio_hang();
                $lich_su_mua_hang = new Lich_su_mua_hang();
                $gio_hang->ds_hang = '';
                $lich_su_mua_hang->ds_ls_mua_hang = '';
                $gio_hang->save();
                $lich_su_mua_hang->save();
                $nguoi_dung = new Nguoi_dung();
                $khach_hang = new Khach_hang();
                $khach_hang->email = $request->email;
                $nguoi_dung->tai_khoan = $request->tai_khoan;
                $khach_hang->tai_khoan = $request->tai_khoan;
                $nguoi_dung->mat_khau = md5($request->mat_khau);
                $nguoi_dung->ma_chuc_nang = 3;
                $nguoi_dung->trang_thai = 1;
                $khach_hang->ma_ls_mua_hang = $lich_su_mua_hang->ma_ls_mua_hang;
                $khach_hang->ma_gio_hang = $gio_hang->ma_gio_hang;
                $nguoi_dung->save();
                $khach_hang->save();
            }
            else{
                session()->put('bao_loi', 'Xác nhận mật khẩu không khớp.');
                return redirect()->route('dang_ky');
            }
        }
        else{
            session()->put('bao_loi', 'Tài khoản đã tồn tại');
            return redirect()->route('dang_ky');
        }
		return redirect()->route('dang_nhap');
	}
    public function view_quan_ly_khach_hang($page)
	{
		if (session('nguoi_dung') != null) {
            $all_khach_hangs = Khach_hang::all();
			$data = [];
			$page_length = 6;
			$data['khach_hangs'] =  $all_khach_hangs->skip(($page - 1) * $page_length)->take($page_length);
			$page_number = (int)($all_khach_hangs->count() / $page_length);
			if ($all_khach_hangs->count() % $page_length > 0) {
				$page_number++;
			}
			$data['page_number'] = $page_number;
			$data['page'] = $page;
			return view('admin.Quan_ly_khach_hang.quan_ly_khach_hang', $data);
		} else {
			return redirect()->route('dang_nhap');
		}
	}
    public function xu_ly_xoa($ma_khach_hang){
		$khach_hang = Khach_hang::find($ma_khach_hang);
		$nguoi_dung = Nguoi_dung::where('tai_khoan','=',$khach_hang->tai_khoan)->first();
        $gio_hang = Gio_hang::where('ma_gio_hang','=',$khach_hang->ma_gio_hang)->first();
        $lich_su_mua_hang = Lich_su_mua_hang::where('ma_ls_mua_hang','=',$khach_hang->ma_ls_mua_hang)->first();
        $khach_hang->delete();
        $nguoi_dung->delete();
		$gio_hang->delete();
        $lich_su_mua_hang->delete();
		return redirect()->route('quan_ly_khach_hang',1);
	}
    public function xu_ly_sua(Request $request){
        $khach_hang = Khach_hang::find($request->update_ma);
        $nguoi_dung = Nguoi_dung::find($khach_hang->tai_khoan);
        $nguoi_dung->trang_thai = $request->update_state;
        $nguoi_dung->save();	
        return redirect()->route('quan_ly_khach_hang', 1);
    }
    public function xu_ly_tim_kiem(Request $request)
	{
		$ten_tai_khoan = $request->ten_tai_khoan;
        $email = $request->s_email;
		$trang_thai = $request->trang_thai;
        
		if($ten_tai_khoan==null&&$email==null){
            $ten_tai_khoan = '\0';
            $email = '\0';
		}
        elseif($ten_tai_khoan==null&&$email!=null){
            $ten_tai_khoan = '\0';
        }
        elseif($ten_tai_khoan!=null&&$email==null){
            $email = '\0';
        }
        return redirect()->route('quan_ly_khach_hang_search',['tk'=>$ten_tai_khoan, 'email'=>$email, 'state'=>$trang_thai, 'page'=>1]);
	}
    public function view_tim_kiem($ten_tai_khoan, $email, $state, $page)
    {
        if (session('nguoi_dung') != null) {
            if($ten_tai_khoan!='\0'&&$email!='\0'){
                if($state==-1){
                    $tim_kiems = Khach_hang::where('tai_khoan','like','%'.$ten_tai_khoan. '%')
                    ->where('email','like','%'.$email. '%')->get();
                }
                else{
                    $tim_kiems = Khach_hang::join('nguoi_dung','nguoi_dung.tai_khoan','=','khach_hang.tai_khoan')
                    ->where('nguoi_dung.tai_khoan','like','%'.$ten_tai_khoan. '%')
                    ->where('email','like','%'.$email. '%')
                    ->where('trang_thai','=',$state)->get();
                }
            }
            elseif($ten_tai_khoan=='\0'&&$email!='\0'){
                if($state==-1){
                    $tim_kiems = Khach_hang::where('email','like','%'.$email. '%')->get();
                }
                else{
                    $tim_kiems = Khach_hang::join('nguoi_dung','nguoi_dung.tai_khoan','=','khach_hang.tai_khoan')
                    ->where('email','like','%'.$email. '%')
                    ->where('trang_thai','=',$state)->get();
                }
            }
            elseif($ten_tai_khoan!='\0'&&$email=='\0'){
                if($state==-1){
                    $tim_kiems = Khach_hang::where('tai_khoan','like','%'.$ten_tai_khoan. '%')->get();
                }
                else{
                    $tim_kiems = Khach_hang::join('nguoi_dung','nguoi_dung.tai_khoan','=','khach_hang.tai_khoan')
                    ->where('nguoi_dung.tai_khoan','like','%'.$ten_tai_khoan. '%')
                    ->where('trang_thai','=',$state)->get();
                }
            }
            else{
                if($state==-1){
                    $tim_kiems = Khach_hang::all();
                }
                else{
                    $tim_kiems = Khach_hang::join('nguoi_dung','nguoi_dung.tai_khoan','=','khach_hang.tai_khoan')
                    ->where('trang_thai','=',$state)->get();
                }
            }
			
			$data = [];
			$page_length = 6;
			$data['tim_kiems'] =  $tim_kiems->skip(($page - 1) * $page_length)->take($page_length);
			$page_number = (int)($tim_kiems->count() / $page_length);
			if ($tim_kiems->count() % $page_length > 0) {
				$page_number++;
			}
            if ($tim_kiems->count()==0) {
				$data['empty'] = 1;
			}
            else{
                $data['empty'] = 0;
            }
			$data['page_number'] = $page_number;
			$data['page'] = $page;
            $data['tk'] = $ten_tai_khoan;
            $data['email'] = $email;
            $data['state'] = $state;
			return view('admin.Quan_ly_khach_hang.quan_ly_khach_hang_search', $data);
		} else {
			return redirect()->route('dang_nhap');
		}
    }

    public function view_thong_tin_tai_khoan(){
		if (session('nguoi_dung') != null) {
			$data = [];
			$data['bao_loi'] = session('bao_loi');
			session()->put('bao_loi', '');
			$data['nguoi_dung'] = session('nguoi_dung');
			$data['khach_hang'] = Khach_hang::
				// joinRelationship('nguoi_dung')
				join('nguoi_dung','nguoi_dung.tai_khoan','=','khach_hang.tai_khoan')
				->where('trang_thai','=',1)
				->where('khach_hang.tai_khoan','like','%'.$data['nguoi_dung']. '%')
				// where('tai_khoan','like','%'.$data['nguoi_dung']. '%')
				// ->toSql();
				->first();
		}
		else{
            return redirect()->route('home_ds_tuong');
        }
		// echo $data['khach_hang'];
		// echo $data['nguoi_dung'];
		return view('home.Tai_khoan.thong_tin_tai_khoan', $data);
	}
	public function xu_ly_cap_nhat_khach_hang(Request $request){
        $nguoi_dung = Nguoi_dung::find($request->tai_khoan);
		$khach_hang = Khach_hang::where('tai_khoan','=',$request->tai_khoan)->first();
		session()->put('bao_loi', '');
		if(isset($request->email)){
			$khach_hang->email = $request->email;
			$khach_hang->save();
			session()->put('bao_loi', '');
		}
		else{
			session()->put('bao_loi', 'Bạn chưa nhập email');
		}
		if(isset($request->mat_khau)||isset($request->xac_nhan_mat_khau)){
			if($request->xac_nhan_mat_khau==$request->mat_khau){
				$nguoi_dung->mat_khau = md5($request->mat_khau);
				$nguoi_dung->save();
				session()->put('bao_loi', '');
			}
			else{
				session()->put('bao_loi', 'Xác nhận mật khẩu không khớp.');
			}
		}
		// echo $khach_hang;
        return redirect()->route('thong_tin_tai_khoan');
    }
    public function view_lich_su_mua_hang(){
        if (session('nguoi_dung') != null) {
            $data = [];
            // $ds_ls = [];
            $data['nguoi_dung'] = session('nguoi_dung');
            $data['khach_hang'] = Khach_hang::
                where('tai_khoan','like','%'.$data['nguoi_dung']. '%')
            // 	// ->toSql();
                ->first();
            $lich_su = Lich_su_mua_hang::where('ma_ls_mua_hang','=',$data['khach_hang']->ma_ls_mua_hang)->first();
            $data['ds_ls'] = explode(", ",$lich_su->ds_ls_mua_hang);
            $first = true;
            foreach($data['ds_ls'] as $item) {
                $bau_vat = Bau_vat::select('ten_bau_vat as ten_san_pham', 'bau_vat.ma_loai_bau_vat as ma_san_pham', 'hinh_anh', 'gia','loai_san_pham')
                    ->join('loai_bau_vat','bau_vat.ma_loai_bau_vat','=','loai_bau_vat.ma_loai_bau_vat')
                    ->where('ten_bau_vat','like','%'.$item. '%');
                $vat_pham = Vat_pham::select('ten_vat_pham as ten_san_pham', 'vat_pham.ma_loai_vat_pham as ma_san_pham', 'hinh_anh', 'gia','loai_san_pham')
                    ->join('loai_vat_pham','vat_pham.ma_loai_vat_pham','=','loai_vat_pham.ma_loai_vat_pham')
                    ->where('ten_vat_pham','like','%'.$item. '%');
                $trang_phuc = Trang_phuc::select('ten_trang_phuc as ten_san_pham', 'trang_phuc.ma_do_hiem as ma_san_pham', 'trang_phuc.hinh_anh', 'gia','loai_san_pham')
                    ->join('do_hiem','trang_phuc.ma_do_hiem','=','do_hiem.ma_do_hiem')
                    ->where('ten_trang_phuc','like','%'.$item. '%');
                // $data['san_phams']=$trang_phuc;
                if($bau_vat!=null){
                    if($first==true){
                        $data['san_phams']=$bau_vat;
                        $first=false;
                    }
                    else{
                        $data['san_phams']->union($bau_vat);
                    }
                }
                if($vat_pham!=null){
                    if($first==true){
                        $data['san_phams']=$vat_pham;
                        $first=false;
                    }
                    else{
                        $data['san_phams']->union($vat_pham);
                    }
                }
                if($trang_phuc!=null){
                    if($first==true){
                        $data['san_phams']=$trang_phuc;
                        $first=false;
                    }
                    else{
                        $data['san_phams']->union($trang_phuc);
                    }
                }
            }

        }
        else{
            return redirect()->route('home_ds_tuong');
        }
        $data['san_phams']=$data['san_phams']->get();
        // print_r($data['san_phams']); 
        // echo($trang_phuc);
        return view('home.Tai_khoan.lich_su_mua_hang', $data);
    }
    public function xu_ly_them_gio_hang(Request $request)
	{

        $tai_khoan = session('nguoi_dung');
        $khach_hang = Khach_hang::where('tai_khoan','like','%'.$tai_khoan. '%')->first();
		$gio_hang = Gio_hang::where('ma_gio_hang','=',$khach_hang->ma_gio_hang)->first();
        if($gio_hang->ds_hang!= null){
            $gio_hang->ds_hang = $gio_hang->ds_hang.', '.$request->keyword;
        }
        else{
            $gio_hang->ds_hang = $request->keyword;
        }
        $gio_hang->save();
        // print_r ($gio_hang->ds_hang);
		return redirect()->route('gio_hang');
	}
    public function view_gio_hang(){
        return view('home.Tai_khoan.gio_hang');
    }
}
