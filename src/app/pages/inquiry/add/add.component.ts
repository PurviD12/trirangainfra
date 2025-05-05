import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { ToastService } from 'src/app/services/toast.service';
import { ActivatedRoute, Router } from '@angular/router';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { FormControl } from '@angular/forms';
import { InquiryAddAttachmentComponent } from '../../dialog/inquiry-add-attachment/inquiry-add-attachment.component';
import { MatDialog } from '@angular/material/dialog';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-add',
  templateUrl: './add.component.html',
  styleUrls: ['./add.component.css']
})
export class AddComponent implements OnInit {

  constructor(
      private MasterService: MasterServicesService,
      private ToastService: ToastService,
      private router: Router,
      private route: ActivatedRoute,
      private dialog: MatDialog,
  ) { }

  public id: any = "";
  emailPattern = "^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$";
  urlPattern: RegExp = /^https?:\/\/([a-zA-Z0-9-_]+\.)+([a-zA-Z]{2,})(\/[a-zA-Z0-9-._?,'\/\\+&%$#=~]*)?$/;

  ngOnInit() {
      this.route.paramMap.subscribe(params => {
          if (params.get('id')) {
              this.id = atob(params.get('id')!);
              if (this.id) {
                  this.inquiries_get();
              }
          } else {
              this.state_get();
              this.plant_get();
          }
      });

      this.searchControl.valueChanges.pipe(
          debounceTime(300),
          distinctUntilChanged()
      ).subscribe(value => {
          this.filterOptions(value);
      });

      this.searchCity.valueChanges.pipe(
          debounceTime(300),
          distinctUntilChanged()
      ).subscribe(value => {
          this.filterOptionsCity(value);
      });

      this.searchPlant.valueChanges.pipe(
          debounceTime(300),
          distinctUntilChanged()
      ).subscribe(value => {
          this.filterOptionsPlant(value);
      });

      this.source_get();
  }
  ngAfterViewInit() {

  }

  openAttachmentDialog(data_get:any, index="") {
      const dialogRef = this.dialog.open(InquiryAddAttachmentComponent, {
          width: '550px',
          data: {
              index: index,
              name: data_get.name ? data_get.name : "",
              attachment: data_get.attachment ? data_get.attachment : "",
              attachment_full: data_get.attachment_full ? data_get.attachment_full : "",
          }
      });

      dialogRef.afterClosed().subscribe((result:any) => {
          if (result) {
              if (result.index) {
                  this.inquiry_obj.attachment_array[result.index-1].name = result.name;
                  this.inquiry_obj.attachment_array[result.index-1].attachment = result.attachment;
                  this.inquiry_obj.attachment_array[result.index-1].attachment_full = result.attachment_full;
              } else {
                  this.inquiry_obj.attachment_array.push(result);
              }
          }
      });
  }

  public inquiry_obj: any = {
      priority: "warm",
      is_big_buyer: "0",
      attachment_array: [],
  };
  public isSubmitted: boolean = false;
  public button_text: string = "Submit";
  SubmitInquiryForm() {
      if (!this.isSubmitted && this.inquiry_obj.state_id && this.inquiry_obj.city_id) {
          this.isSubmitted = true;
          this.button_text = "Please Wait...";

          var state_id = this.inquiry_obj.state_id;
          var state_name = this.state_list.filter(function (val: any) {
              return val.id == state_id;
          })[0];
          this.inquiry_obj.state_name = state_name.name;

          var city_id = this.inquiry_obj.city_id;
          var city_name = this.city_list.filter(function (val: any) {
              return val.id == city_id;
          })[0];
          this.inquiry_obj.city_name = city_name.name;

          var which_plant_city_id = this.inquiry_obj.which_plant_city_id;
          var plant_name = this.plant_list.filter(function (val: any) {
              return val.city_id == which_plant_city_id;
          })[0];
          this.inquiry_obj.plant_city_name = plant_name.name;

          const obj = this.inquiry_obj;
          const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

          this.MasterService.inquiry_save(mapped).subscribe(res => {
              var response = JSON.parse(JSON.stringify(res));
              if (response.success) {
                  this.ToastService.success(response.message);
                  this.router.navigate(['/dashboard']);
              } else if (response.confirm_popup == 1) {
                  Swal.fire({
                      title: 'Confirmation',
                      text: response.message,
                      icon: 'warning',
                      showCancelButton: true,
                      confirmButtonText: 'Yes',
                      cancelButtonText: 'Cancel',
                  }).then((result) => {
                      if (result.isConfirmed) {
                          this.inquiry_obj.lead_reenter = 1;
                          this.SubmitInquiryForm();
                      }
                  });
              } else {
                  this.ToastService.error(response.message);
              }
              this.isSubmitted = false;
              this.button_text = "Submit";
          }, error => {
              this.isSubmitted = false;
              this.button_text = "Submit";
              this.ToastService.error('Opps...something went wrong, Plesae try again.');
          })
      }
  }

  // public filteredOptions: any = [];
  public isLoadingState: boolean = false;
  public state_list: any = [];
  public state_list_all: any = [];
  public state_obj: any = {};
  state_get() {
      this.isLoadingState = true;
      this.state_list = [];

      this.state_obj.or_state_id = this.inquiry_obj.state_id ? this.inquiry_obj.state_id : "";
      const obj = this.state_obj;
      const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

      this.MasterService.states(mapped).subscribe(res => {
          var response = JSON.parse(JSON.stringify(res));
          if (response.success) {
              this.state_list = response.data;
              // this.state_list_all = response.data;
              // this.state_list_all.forEach(element => {
              //     if (this.state_list.length < 51) {
              //         this.state_list.push(element);
              //     }
              // });
              // this.filteredOptions = this.state_list;
          }
          if (this.id) {
              this.city_obj.or_city_id = this.inquiry_obj.city_id;
              this.city_get();
          }
          this.isLoadingState = false;
      }, error => {
          this.isLoadingState = false;
      })
  }

  state_change() {
      this.inquiry_obj.city_id = "";
      this.city_obj.or_city_id = "";
      this.city_list = [];
      if (this.inquiry_obj.state_id) {
          this.city_get();
      }
  }

  public isLoadingCity: boolean = false;
  public city_list: any = [];
  public city_obj: any = {};
  city_get() {
      this.isLoadingCity = true;
      this.city_list = [];

      this.city_obj.state_id = this.inquiry_obj.state_id;
      this.city_obj.or_city_id = this.inquiry_obj.city_id;
      const obj = this.city_obj;
      const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

      this.MasterService.cities(mapped).subscribe(res => {
          var response = JSON.parse(JSON.stringify(res));
          if (response.success) {
              this.city_list = response.data;
          }
          this.isLoadingDetails = false;
          this.isLoadingCity = false;
      }, error => {
          this.isLoadingCity = false;
      })
  }

  public isLoadingPlant: boolean = false;
  public plant_list: any = [];
  public plant_obj: any = {};
  plant_get() {
      this.isLoadingPlant = true;
      this.plant_list = [];

      this.plant_obj.or_plant_city_id = this.inquiry_obj.which_plant_city_id ? this.inquiry_obj.which_plant_city_id : "";
      const obj = this.plant_obj;
      const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

      this.MasterService.plants(mapped).subscribe(res => {
          var response = JSON.parse(JSON.stringify(res));
          if (response.success) {
              this.plant_list = response.data;
          }
          this.isLoadingDetails = false;
          this.isLoadingPlant = false;
      }, error => {
          this.isLoadingPlant = false;
      })
  }

  public isLoadingSource: boolean = false;
  public source_list: any = [];
  public source_obj: any = {};
  source_get() {
      this.isLoadingSource = true;
      this.source_list = [];

      const obj = this.source_obj;
      const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

      this.MasterService.source_list(mapped).subscribe(res => {
          var response = JSON.parse(JSON.stringify(res));
          if (response.success) {
              this.source_list = response.data;
          }
          this.isLoadingSource = false;
      }, error => {
          this.isLoadingSource = false;
      })
  }

  public isLoadingDetails: boolean = false;
  inquiries_get() {
      this.isLoadingDetails = true;
      this.inquiry_obj = {};

      const obj: { [key: string]: any } =  {
          id: this.id
      };
      const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

      this.MasterService.inquiries_list(mapped).subscribe(res => {
          var response = JSON.parse(JSON.stringify(res));
          if (response.success) {
              this.inquiry_obj = response.data[0];
              this.inquiry_obj.contact_no = this.inquiry_obj.contact_no.replace("91 ", "");
              this.state_obj.or_state_id = this.inquiry_obj.state_id;
              this.state_get();
              this.plant_obj.or_plant_city_id = this.inquiry_obj.which_plant_city_id;
              this.plant_get();

              if (this.inquiry_obj.unit) {
                  var type_get = this.inquiry_obj.unit;
                  var show_obj_get = this.unit_list.filter(function (val: any) {
                      return val.type == type_get;
                  })[0];

                  this.show_rate_get = show_obj_get.show_rate;
              }
          }
      }, error => {
          this.isLoadingDetails = false;
      })
  }


  selectedOption = new FormControl();
  selectedOptionCity = new FormControl();
  searchControl = new FormControl();
  searchCity = new FormControl();
  searchPlant = new FormControl();



  filterOptions(value: string) {
      this.state_obj.search = value;
      if (value) {
          this.state_get();
      }
      // const filterValue = value.toLowerCase();
      // this.filteredOptions = this.state_list.filter(option =>
      //     option.name.toLowerCase().includes(filterValue)
      // );
  }

  filterOptionsCity(value: string) {
      this.city_obj.search = value;
      if (value) {
          this.city_get();
      }
      // const filterValue = value.toLowerCase();
      // this.filteredOptions = this.state_list.filter(option =>
      //     option.name.toLowerCase().includes(filterValue)
      // );
  }

  filterOptionsPlant(value: string) {
      this.plant_obj.search = value;
      if (value) {
          this.plant_get();
      }
      // const filterValue = value.toLowerCase();
      // this.filteredOptions = this.state_list.filter(option =>
      //     option.name.toLowerCase().includes(filterValue)
      // );
  }


  public unit_list: any = [
      {
          title: "Sqft",
          type: "Sqft",
          show_rate: 1,
      },
      {
          title: "Running Ft",
          type: "Running Ft",
          show_rate: 1,
      },
      {
          title: "Running Meter",
          type: "Running Meter",
          show_rate: 1,
      },
      {
          title: "Sq Meter",
          type: "Sq Meter",
          show_rate: 1,
      },
      {
          title: "Cubic Meter",
          type: "Cubic Meter",
          show_rate: 1,
      },
      {
          title: "Nos",
          type: "Nos",
          show_rate: 2,
      },
  ];

  public show_rate_get: any = "";
  unit_change(type:any) {
      var type_get = type;
      var show_obj_get = this.unit_list.filter(function (val: any) {
          return val.type == type_get;
      })[0];

      this.show_rate_get = show_obj_get.show_rate;

      this.inquiry_obj.lead_value = "";
      this.inquiry_obj.rate = "";
      this.inquiry_obj.quantity = "";
      this.inquiry_obj.rate_of_panel = "";
      this.inquiry_obj.nos_of_panel = "";
      this.inquiry_obj.rate_of_column = "";
      this.inquiry_obj.nos_of_column = "";
  }

  value_count(type = "") {
      this.inquiry_obj.lead_value = "";
      if (type == "nos" && this.inquiry_obj.rate_of_panel && this.inquiry_obj.nos_of_panel && this.inquiry_obj.rate_of_column && this.inquiry_obj.nos_of_column) {
          this.inquiry_obj.lead_value = (this.inquiry_obj.rate_of_panel * this.inquiry_obj.nos_of_panel) + (this.inquiry_obj.rate_of_column * this.inquiry_obj.nos_of_column);
      } else if (this.inquiry_obj.quantity && this.inquiry_obj.rate) {
          this.inquiry_obj.lead_value = (this.inquiry_obj.rate * this.inquiry_obj.quantity);
      }
  }


  // onFileSelected(event: any) {
  //     const file: File = event.target.files[0];
  //     this.inquiry_obj.attach = file ? file.name : '';
  //     // Do something with the selected file
  //     console.log(file);
  // }

  @ViewChild('fileInput', { static: false }) fileInputRef!: ElementRef<HTMLInputElement>;

  onFileSelected(event: any) {
      const file: File = event.target.files[0];
      this.inquiry_obj.attachment = file;
      if (file && file.type != "application/pdf") {
          this.inquiry_obj.attachment_file = "";
      }

      const maxFileSize = 10 * 1024 * 1024;
      if (file.size > maxFileSize) {
          this.inquiry_obj.attachment_file = "";
          this.ToastService.error("Oops... You can upload files up to 10MB in size.");
      }
  }

  openFileInput() {
      this.fileInputRef.nativeElement.click();
  }
}
